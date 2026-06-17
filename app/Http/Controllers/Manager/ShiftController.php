<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreShiftRequest;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\NguoiDung;
use App\Mail\NextWeekScheduleMail;
use App\Mail\ShiftDeletedMail;
use App\Mail\ShiftUpdatedMail;
use App\Notifications\ScheduleSentNotification;
use App\Notifications\ShiftDeletedNotification;
use App\Notifications\ShiftUpdatedNotification;
use App\Services\ShiftService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
    ) {}
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $validatedFilters = $request->validate([
            'ngay_bat_dau' => ['nullable', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $selectedStartDate = (string) ($validatedFilters['ngay_bat_dau'] ?? $today);
        $selectedEndDate = (string) ($validatedFilters['ngay_ket_thuc'] ?? $today);

        if ($selectedEndDate < $selectedStartDate) {
            [$selectedStartDate, $selectedEndDate] = [$selectedEndDate, $selectedStartDate];
        }

        $search = trim((string) $request->input('search', ''));

        $shifts = CaLamViec::query()
            ->selectRaw('MIN(id) as id, ngay_lam, ten_ca, gio_bat_dau, gio_ket_thuc, COUNT(*) as so_nhan_su')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $sub) use ($search) {
                    $sub->where('ten_ca', 'like', "%{$search}%")
                        ->orWhereHas('nguoiDung', function (Builder $userQuery) use ($search) {
                            $userQuery->where('ho_ten', 'like', "%{$search}%");
                        });
                });
            })
            ->when(!empty($selectedStartDate), function (Builder $query) use ($selectedStartDate) {
                $query->whereDate('ngay_lam', '>=', $selectedStartDate);
            })
            ->when(!empty($selectedEndDate), function (Builder $query) use ($selectedEndDate) {
                $query->whereDate('ngay_lam', '<=', $selectedEndDate);
            })
            ->groupBy('ngay_lam', 'ten_ca', 'gio_bat_dau', 'gio_ket_thuc')
            ->orderBy('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->paginate(10)
            ->withQueryString();

        return view('manager.shifts.index', [
            'shifts' => $shifts,
            'selectedStartDate' => $selectedStartDate,
            'selectedEndDate' => $selectedEndDate,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('manager.shifts.create');
    }

    public function edit(Request $request, int $id)
    {
        $shift = CaLamViec::findOrFail($id);
        $editMode = (string) $request->query('mode', 'all');
        if (!in_array($editMode, ['all', 'info', 'staff'], true)) {
            $editMode = 'all';
        }

        $shiftGroup = $this->shiftService->buildShiftGroupQuery($shift)
            ->with('nguoiDung')
            ->get(['id', 'nguoi_dung_id', 'ten_ca', 'ngay_lam', 'gio_bat_dau', 'gio_ket_thuc']);

        $assignmentData = $this->shiftService->buildShiftAssignmentData();

        $assignedManagerIds = $shiftGroup
            ->filter(function (CaLamViec $groupShift) {
                return in_array((string) ($groupShift->nguoiDung?->vai_tro ?? ''), ['quản lý', 'admin'], true);
            })
            ->pluck('nguoi_dung_id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $assignedStaffIds = $shiftGroup
            ->filter(function (CaLamViec $groupShift) {
                return (string) ($groupShift->nguoiDung?->vai_tro ?? '') === 'nhân viên';
            })
            ->pluck('nguoi_dung_id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        return view('manager.shifts.edit', array_merge($assignmentData, [
            'shift' => $shift,
            'totalAssignedUsers' => $shiftGroup->count(),
            'assignedManagerIds' => $assignedManagerIds,
            'assignedStaffIds' => $assignedStaffIds,
            'editMode' => $editMode,
        ]));
    }

    public function store(Request $request)
    {
        $formRequest = new StoreShiftRequest();
        $validatedShift = $request->validate($formRequest->rules(), $formRequest->messages());

        $request->merge([
            'selected_user_ids' => collect($request->input('selected_user_ids', []))
                ->filter(fn ($id) => is_scalar($id) && trim((string) $id) !== '')
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'selected_user_ids' => ['required', 'array', 'min:1'],
            'selected_user_ids.*' => ['integer', 'exists:nguoi_dung,id'],
        ], [
            'selected_user_ids.required' => 'Vui lòng chọn ít nhất một nhân sự rảnh để phân ca.',
            'selected_user_ids.min' => 'Vui lòng chọn ít nhất một nhân sự rảnh để phân ca.',
        ]);

        $selectedUserIds = collect($validated['selected_user_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validUserIds = NguoiDung::query()
            ->whereIn('id', $selectedUserIds)
            ->where('trang_thai', 'hoạt động')
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->pluck('id');

        if ($validUserIds->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_user_ids' => 'Danh sách nhân sự được chọn không hợp lệ hoặc không ở trạng thái hoạt động.',
            ]);
        }

        $conflict = $this->shiftService->findShiftTimeConflictForUsers(
            (string) $validatedShift['ngay_lam'],
            (string) $validatedShift['gio_bat_dau'],
            (string) $validatedShift['gio_ket_thuc'],
            $validUserIds->map(fn ($id) => (int) $id)
        );

        if ($conflict) {
            throw ValidationException::withMessages([
                'gio_bat_dau' => 'Đã có ca ' . $conflict['shift_name'] . ' trùng giờ làm với ' . $conflict['user_name']
                    . ' (' . $conflict['existing_start'] . ' - ' . $conflict['existing_end'] . ').',
            ]);
        }

        $timestamp = now();
        $rows = $validUserIds->map(function (int $userId) use ($validatedShift, $timestamp) {
            return [
                'nguoi_dung_id' => $userId,
                'ten_ca' => $validatedShift['ten_ca'],
                'ngay_lam' => $validatedShift['ngay_lam'],
                'gio_bat_dau' => $validatedShift['gio_bat_dau'],
                'gio_ket_thuc' => $validatedShift['gio_ket_thuc'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        });

        CaLamViec::query()->insert($rows->all());

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Đã thêm ca làm việc cho ' . $validUserIds->count() . ' nhân sự.');
    }

    /**
     * Danh sách nhân sự đang rảnh (không có ca trùng giờ) cho ngày + giờ đã chọn.
     */
    public function availableUsers(Request $request)
    {
        $validated = $request->validate([
            'ngay_lam' => ['required', 'date'],
            'gio_bat_dau' => ['required', 'date_format:H:i'],
            'gio_ket_thuc' => ['required', 'date_format:H:i', 'different:gio_bat_dau'],
            'exclude_shift_id' => ['nullable', 'integer', 'exists:ca_lam_viec,id'],
        ]);

        // Khi sửa ca: loại trừ chính nhóm ca đang sửa khỏi kiểm tra trùng giờ,
        // để nhân sự đang thuộc ca này vẫn hiện ra (được chọn lại).
        $excludeShiftIds = collect();
        if (! empty($validated['exclude_shift_id'])) {
            $editingShift = CaLamViec::find($validated['exclude_shift_id']);
            if ($editingShift) {
                $excludeShiftIds = $this->shiftService->buildShiftGroupQuery($editingShift)->pluck('id');
            }
        }

        $candidates = NguoiDung::query()
            ->with(['hoSoNhanVien.chucVu', 'hoSoQuanLy.chucVu'])
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->where('trang_thai', 'hoạt động')
            ->get();

        $availableIds = $this->shiftService->filterAvailableUsersForSlot(
            $candidates->pluck('id')->map(fn ($id) => (int) $id),
            (string) $validated['ngay_lam'],
            (string) $validated['gio_bat_dau'],
            (string) $validated['gio_ket_thuc'],
            collect(),
            $excludeShiftIds
        )->all();

        // Gom nhân sự rảnh theo chức vụ. loai_order: quản lý (0) → nhân viên (1) → chưa gán (2)
        $groups = [];
        foreach ($candidates->whereIn('id', $availableIds) as $user) {
            $isManager = (string) $user->vai_tro === 'quản lý';

            if ($isManager) {
                $tenChucVu = trim((string) ($user->hoSoQuanLy?->chucVu?->ten_chuc_vu ?? '')) ?: 'Quản lý';
                $loaiOrder = 0;
            } else {
                $tenChucVu = trim((string) ($user->hoSoNhanVien?->chucVu?->ten_chuc_vu ?? ''));
                if ($tenChucVu !== '') {
                    $loaiOrder = 1;
                } else {
                    $tenChucVu = 'Chưa gán chức vụ';
                    $loaiOrder = 2;
                }
            }

            $key = $loaiOrder . '|' . $tenChucVu;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'ten_chuc_vu' => $tenChucVu,
                    'loai_order' => $loaiOrder,
                    'users' => [],
                ];
            }

            $groups[$key]['users'][] = [
                'id' => (int) $user->id,
                'ho_ten' => $user->ho_ten ?? ('Người dùng #' . $user->id),
            ];
        }

        $orderedGroups = collect($groups)
            ->sortBy([
                fn ($g) => $g['loai_order'],
                fn ($g) => $g['ten_chuc_vu'],
            ])
            ->map(function (array $g) {
                $g['users'] = collect($g['users'])
                    ->sortBy('ho_ten', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();
                return $g;
            })
            ->values();

        return response()->json(['groups' => $orderedGroups]);
    }

    public function update(Request $request, int $id)
    {
        $shift = CaLamViec::findOrFail($id);

        $editMode = (string) $request->input('edit_mode', 'all');
        if (!in_array($editMode, ['all', 'info', 'staff'], true)) {
            throw ValidationException::withMessages([
                'edit_mode' => 'Chế độ sửa không hợp lệ.',
            ]);
        }

        $groupShifts = $this->shiftService->buildShiftGroupQuery($shift)
            ->get(['id', 'nguoi_dung_id']);

        $groupShiftIds = $groupShifts->pluck('id');
        $groupUserIds = $groupShifts
            ->pluck('nguoi_dung_id')
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values();

        $oldShift = [
            'ten_ca' => $shift->ten_ca,
            'ngay_lam' => $shift->ngay_lam ? Carbon::parse($shift->ngay_lam)->format('Y-m-d') : now()->toDateString(),
            'gio_bat_dau' => Carbon::parse($shift->gio_bat_dau)->format('H:i'),
            'gio_ket_thuc' => Carbon::parse($shift->gio_ket_thuc)->format('H:i'),
        ];
        $shiftPayload = $oldShift;

        if (in_array($editMode, ['all', 'info'], true)) {
            $validatedShift = $request->validate([
                'ten_ca' => ['required', 'string', 'max:100'],
                'ngay_lam' => ['required', 'date'],
                'gio_bat_dau' => ['required', 'date_format:H:i'],
                'gio_ket_thuc' => ['required', 'date_format:H:i', 'different:gio_bat_dau'],
            ]);

            $shiftPayload = [
                'ten_ca' => $validatedShift['ten_ca'],
                'ngay_lam' => $validatedShift['ngay_lam'],
                'gio_bat_dau' => $validatedShift['gio_bat_dau'],
                'gio_ket_thuc' => $validatedShift['gio_ket_thuc'],
            ];
        }

        $targetUserIds = $groupUserIds;
        if (in_array($editMode, ['all', 'staff'], true)) {
            $request->merge([
                'selected_user_ids' => collect($request->input('selected_user_ids', []))
                    ->filter(fn ($id) => is_scalar($id) && trim((string) $id) !== '')
                    ->values()
                    ->all(),
            ]);

            $validatedMembers = $request->validate([
                'selected_user_ids' => ['required', 'array', 'min:1'],
                'selected_user_ids.*' => ['integer', 'exists:nguoi_dung,id'],
            ], [
                'selected_user_ids.required' => 'Vui lòng chọn ít nhất một nhân sự cho ca làm.',
                'selected_user_ids.min' => 'Vui lòng chọn ít nhất một nhân sự cho ca làm.',
            ]);

            $selectedUserIds = collect($validatedMembers['selected_user_ids'])
                ->map(fn ($userId) => (int) $userId)
                ->unique()
                ->values();

            $validUserIds = NguoiDung::query()
                ->whereIn('id', $selectedUserIds)
                ->where('trang_thai', 'hoạt động')
                ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
                ->pluck('id')
                ->map(fn ($userId) => (int) $userId)
                ->values();

            if ($validUserIds->count() !== $selectedUserIds->count()) {
                throw ValidationException::withMessages([
                    'selected_user_ids' => 'Danh sách nhân sự chọn có tài khoản không hợp lệ hoặc không ở trạng thái hoạt động.',
                ]);
            }

            $targetUserIds = $validUserIds;
        }

        $conflict = $this->shiftService->findShiftTimeConflictForUsers(
            (string) $shiftPayload['ngay_lam'],
            (string) $shiftPayload['gio_bat_dau'],
            (string) $shiftPayload['gio_ket_thuc'],
            $targetUserIds,
            $groupShiftIds
        );

        if ($conflict) {
            throw ValidationException::withMessages([
                'gio_bat_dau' => 'Đã có ca ' . $conflict['shift_name'] . ' trùng giờ làm với ' . $conflict['user_name']
                    . ' (' . $conflict['existing_start'] . ' - ' . $conflict['existing_end'] . ').',
            ]);
        }

        $groupByUser = $groupShifts->keyBy(function (CaLamViec $groupShift) {
            return (int) $groupShift->nguoi_dung_id;
        });

        $currentUserIds = $groupUserIds;
        $usersToKeep = $targetUserIds->intersect($currentUserIds)->values();
        $usersToAdd = $targetUserIds->diff($currentUserIds)->values();
        $usersToRemove = $currentUserIds->diff($targetUserIds)->values();

        $shiftIdsToKeep = $usersToKeep
            ->map(fn (int $userId) => $groupByUser->get($userId)?->id)
            ->filter()
            ->values();

        $shiftIdsToRemove = $usersToRemove
            ->map(fn (int $userId) => $groupByUser->get($userId)?->id)
            ->filter()
            ->values();

        if ($shiftIdsToKeep->isNotEmpty()) {
            CaLamViec::query()
                ->whereIn('id', $shiftIdsToKeep)
                ->update([
                    'ten_ca' => $shiftPayload['ten_ca'],
                    'ngay_lam' => $shiftPayload['ngay_lam'],
                    'gio_bat_dau' => $shiftPayload['gio_bat_dau'],
                    'gio_ket_thuc' => $shiftPayload['gio_ket_thuc'],
                    'updated_at' => now(),
                ]);
        }

        if ($shiftIdsToRemove->isNotEmpty()) {
            $removedUsers = NguoiDung::whereIn('id', $usersToRemove)->whereNotNull('email')->get();

            ChamCong::query()
                ->whereIn('ca_lam_viec_id', $shiftIdsToRemove)
                ->delete();

            CaLamViec::query()
                ->whereIn('id', $shiftIdsToRemove)
                ->delete();

            $ngayLamFormatted = Carbon::parse($oldShift['ngay_lam'])->format('d/m/Y');
            foreach ($removedUsers as $user) {
                Mail::to($user->email)->send(new ShiftDeletedMail($user, $oldShift));
                $user->notify(new ShiftDeletedNotification($oldShift['ten_ca'], $ngayLamFormatted));
            }
        }

        if ($usersToAdd->isNotEmpty()) {
            $timestamp = now();
            $insertRows = $usersToAdd->map(function (int $userId) use ($shiftPayload, $timestamp) {
                return [
                    'nguoi_dung_id' => $userId,
                    'ten_ca' => $shiftPayload['ten_ca'],
                    'ngay_lam' => $shiftPayload['ngay_lam'],
                    'gio_bat_dau' => $shiftPayload['gio_bat_dau'],
                    'gio_ket_thuc' => $shiftPayload['gio_ket_thuc'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            });

            CaLamViec::query()->insert($insertRows->all());
        }

        if ($editMode === 'info') {
            $this->notifyShiftUpdated($groupUserIds, $oldShift, $shiftPayload);

            return redirect()
                ->route('manager.shifts.show', $shift->id)
                ->with('success', 'Đã cập nhật thông tin ca làm việc.');
        }

        if ($editMode === 'staff') {
            return redirect()
                ->route('manager.shifts.show', $shift->id)
                ->with('success', 'Đã cập nhật danh sách nhân sự trong ca.');
        }

        $this->notifyShiftUpdated($targetUserIds, $oldShift, $shiftPayload);

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Đã cập nhật đầy đủ ca làm và nhân sự cho ' . $targetUserIds->count() . ' người.');
    }

    public function show(int $id)
    {
        $shift = CaLamViec::with(['nguoiDung.hoSoNhanVien'])->findOrFail($id);
        // Ký tương đối (không gồm host) rồi bọc thành URL tuyệt đối cho QR.
        // Nhờ vậy chữ ký vẫn hợp lệ dù quét/đăng nhập qua host khác (LAN IP, 127.0.0.1, domain...).
        $checkinQrUrl = url(URL::temporarySignedRoute(
            'shifts.checkin.scan',
            now()->addHours(12),
            ['id' => $shift->id],
            false
        ));
        $checkinQrImageUrl = 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::format('svg')->size(300)->generate($checkinQrUrl)
        );

        $shiftGroup = $this->shiftService->buildShiftGroupQuery($shift)
            ->with(['nguoiDung.hoSoNhanVien'])
            ->get()
            ->sortBy(function (CaLamViec $groupShift) {
                $role = (string) ($groupShift->nguoiDung?->vai_tro ?? '');

                return in_array($role, ['quản lý', 'admin'], true) ? 0 : 1;
            })
            ->values();

        $attendanceMap = ChamCong::query()
            ->whereIn('ca_lam_viec_id', $shiftGroup->pluck('id'))
            ->get()
            ->keyBy('ca_lam_viec_id');

        $shiftDurationHours = $this->shiftService->shiftDurationHours($shift);

        $memberDetails = $shiftGroup->map(function (CaLamViec $groupShift) use ($attendanceMap, $shift, $shiftDurationHours) {
            $attendance = $attendanceMap->get($groupShift->id);
            $checkIn = $attendance?->cham_cong_vao ? Carbon::parse($attendance->cham_cong_vao) : null;
            $checkOut = $attendance?->cham_cong_ra ? Carbon::parse($attendance->cham_cong_ra) : null;

            $plannedStart = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, $shift->gio_bat_dau);
            $plannedEnd = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, $shift->gio_ket_thuc);
            if ($plannedEnd->lessThanOrEqualTo($plannedStart)) {
                $plannedEnd->addDay();
            }

            if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
                $workedHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);
            } else {
                $workedHours = $shiftDurationHours;
            }

                                    $noteParts = collect([
                $attendance?->ghi_chu,
            ])
                ->map(fn($note) => trim((string) $note))
                ->filter()
                ->values();

            return [
                'nguoi_dung_id' => (int) $groupShift->nguoi_dung_id,
                'ca_lam_viec_id' => (int) $groupShift->id,
                'email' => $groupShift->nguoiDung?->email,
                'nhan_su' => $groupShift->nguoiDung?->ho_ten ?? $groupShift->nguoiDung?->hoSoNhanVien?->ho_ten ?? '—',
                'ma_nhan_vien' => $groupShift->nguoiDung?->hoSoNhanVien?->ma_nhan_vien ?? $groupShift->nguoiDung?->hoSoQuanLy?->ma_quan_ly ?? 'Không có mã',
                'vai_tro' => $groupShift->nguoiDung?->vai_tro ?? '—',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'so_gio' => $workedHours,
                'ghi_chu' => $groupShift->nguoiDung?->vai_tro === 'quản lý' ? 'Miễn chấm công' : ($noteParts->isNotEmpty() ? $noteParts->implode(' | ') : 'Chưa có ghi chú'),
                'can_force_checkin' => $checkIn === null && $groupShift->nguoiDung?->vai_tro !== 'quản lý',
                'can_force_checkout' => $checkIn !== null && $checkOut === null && $groupShift->nguoiDung?->vai_tro !== 'quản lý',
            ];
        });

                $shiftStart = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, $shift->gio_bat_dau);
        $shiftEnd = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, $shift->gio_ket_thuc);
        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }
        $isShiftActive = now()->between($shiftStart, $shiftEnd);

                $shiftStart = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, $shift->gio_bat_dau);
        $shiftEnd = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, $shift->gio_ket_thuc);
        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }
        $isShiftActive = now()->between($shiftStart, $shiftEnd);

        return view('manager.shifts.show', [
            'shift' => $shift,
            'memberDetails' => $memberDetails,
            'shiftDurationHours' => $shiftDurationHours,
            'totalAssignedUsers' => $shiftGroup->count(),
            'checkinQrUrl' => $checkinQrUrl,
            'checkinQrImageUrl' => $checkinQrImageUrl,
            'isShiftActive' => $isShiftActive,
        ]);
    }

    public function scanCheckIn(Request $request, int $id)
    {
        $actor = Auth::guard('nguoi_dung')->user() ?? $request->user();
        if (! $actor) {
            abort(403, 'Bạn cần đăng nhập để chấm công bằng QR.');
        }

        // Kiểm tra chữ ký kiểu relative (bỏ qua host) để QR dùng được dù quét/đăng nhập
        // qua host khác (LAN IP, 127.0.0.1, domain...). Hết hạn/không hợp lệ → báo rõ thay vì 403.
        if (! $request->hasValidRelativeSignature()) {
            return redirect()
                ->route($this->resolveCheckinRedirectRoute($actor))
                ->with('error', 'Mã QR chấm công đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu quản lý tạo lại QR.');
        }

        $shift = CaLamViec::findOrFail($id);
        $assignedShift = $this->shiftService->buildShiftGroupQuery($shift)
            ->where('nguoi_dung_id', $actor->id)
            ->first();

        if (! $assignedShift) {
            return redirect()
                ->route($this->resolveCheckinRedirectRoute($actor))
                ->with('error', 'Bạn không thuộc ca làm việc này.');
        }

        $attendance = ChamCong::firstOrNew([
            'nguoi_dung_id' => $actor->id,
            'ca_lam_viec_id' => $assignedShift->id,
        ]);

        if ($actor->vai_tro === 'quản lý') {
            return redirect()
                ->route($this->resolveCheckinRedirectRoute($actor))
                ->with('info', 'Tài khoản quản lý không yêu cầu chấm công.');
        }

        // Đã check-in và check-out rồi
        if ($attendance->cham_cong_vao && $attendance->cham_cong_ra) {
            return redirect()
                ->route($this->resolveCheckinRedirectRoute($actor))
                ->with('info', 'Bạn đã hoàn thành chấm công ca ' . $assignedShift->ten_ca . '.');
        }

        // Đã check-in, chưa check-out → ghi check-out
        if ($attendance->cham_cong_vao && ! $attendance->cham_cong_ra) {
            $now = now();
            $existingNote = trim((string) ($attendance->ghi_chu ?? ''));
            $checkoutNote = 'Check-out bằng QR lúc ' . $now->format('H:i d/m/Y') . '.';
            $attendance->cham_cong_ra = $now;
            $attendance->ghi_chu = $existingNote !== '' ? $existingNote . ' | ' . $checkoutNote : $checkoutNote;
            $attendance->save();

            return redirect()
                ->route($this->resolveCheckinRedirectRoute($actor))
                ->with('success', 'Đã check-out ca ' . $assignedShift->ten_ca . ' thành công.');
        }

        // Chưa check-in → ghi check-in
        $now = now();
        $attendance->cham_cong_vao = $now;
        $attendance->ghi_chu = 'Check-in bằng QR lúc ' . $now->format('H:i d/m/Y') . '.';
        $attendance->save();

        return redirect()
            ->route($this->resolveCheckinRedirectRoute($actor))
            ->with('success', 'Đã check-in ca ' . $assignedShift->ten_ca . ' thành công.');
    }

            private function resolveCheckinRedirectRoute(NguoiDung $actor): string
    {
        return $actor->vai_tro === 'nhân viên'
            ? 'staff.dashboard'
            : 'manager.shifts.index';
    }

    public function forceCheckin(int $id, int $userId)
    {
        $shift = CaLamViec::findOrFail($id);

        $targetShift = $this->shiftService->buildShiftGroupQuery($shift)
            ->where('nguoi_dung_id', $userId)
            ->firstOrFail();

        $attendance = ChamCong::firstOrNew([
            'nguoi_dung_id' => $userId,
            'ca_lam_viec_id' => $targetShift->id,
        ]);

        if ($attendance->cham_cong_vao) {
            return redirect()
                ->route('manager.shifts.show', $id)
                ->with('success', 'Nhân sự này đã được chấm công vào.');
        }

        $resolvedCheckIn = now();
        $plannedStart = $this->shiftService->resolveShiftDateTime($targetShift->ngay_lam, (string)$targetShift->gio_bat_dau);
        $deviationNote = $this->shiftService->buildTimeDeviationNote($resolvedCheckIn, $plannedStart, 'vào ca');
        $autoNote = 'Quản lý chấm công vào hộ lúc ' . $resolvedCheckIn->format('d/m/Y H:i');
        $existingNote = trim((string) ($attendance->ghi_chu ?? ''));

        $attendance->cham_cong_vao = $resolvedCheckIn;
        $finalNotes = array_filter([$existingNote, $deviationNote, $autoNote]);
        $attendance->ghi_chu = implode(' | ', $finalNotes);
        $attendance->save();

        return redirect()
            ->route('manager.shifts.show', $id)
            ->with('success', 'Đã chấm công vào hộ cho nhân sự.');
    }

    public function forceCheckout(int $id, int $userId)
    {
        $shift = CaLamViec::findOrFail($id);

        $targetShift = $this->shiftService->buildShiftGroupQuery($shift)
            ->where('nguoi_dung_id', $userId)
            ->firstOrFail();

        $attendance = ChamCong::where('nguoi_dung_id', $userId)
            ->where('ca_lam_viec_id', $targetShift->id)
            ->firstOrFail();

        if ($attendance->cham_cong_ra) {
            return redirect()
                ->route('manager.shifts.show', $id)
                ->with('success', 'Nhân sự này đã được kết thúc ca.');
        }

        $resolvedCheckOut = now();
        $plannedEnd = $this->shiftService->resolveShiftDateTime($targetShift->ngay_lam, (string)$targetShift->gio_ket_thuc);
        if ($plannedEnd->lessThanOrEqualTo($this->shiftService->resolveShiftDateTime($targetShift->ngay_lam, (string)$targetShift->gio_bat_dau))) {
            $plannedEnd->addDay();
        }
        $deviationNote = $this->shiftService->buildTimeDeviationNote($resolvedCheckOut, $plannedEnd, 'ra ca');
        $autoNote = 'Quản lý kết thúc ca hộ lúc ' . $resolvedCheckOut->format('d/m/Y H:i');
        $existingNote = trim((string) ($attendance->ghi_chu ?? ''));

        $attendance->cham_cong_ra = $resolvedCheckOut;
        $finalNotes = array_filter([$existingNote, $deviationNote, $autoNote]);
        $attendance->ghi_chu = implode(' | ', $finalNotes);
        $attendance->save();

        return redirect()
            ->route('manager.shifts.show', $id)
            ->with('success', 'Đã kết thúc ca hộ cho nhân sự.');
    }

    private function notifyShiftUpdated(Collection $userIds, array $oldShift, array $newShift): void
    {
        $users = NguoiDung::whereIn('id', $userIds)
            ->whereNotNull('email')
            ->get();

        foreach ($users as $user) {
            Mail::to($user->email)->send(new ShiftUpdatedMail($user, $oldShift, $newShift));
            $user->notify(new ShiftUpdatedNotification());
        }
    }

    public function sendNextWeekSchedule(): \Illuminate\Http\RedirectResponse
    {
        $fromDate = Carbon::now()->startOfWeek(CarbonInterface::MONDAY)->addWeek();
        $toDate = $fromDate->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $fromStr = $fromDate->format('d/m/Y');
        $toStr = $toDate->format('d/m/Y');

        $shifts = CaLamViec::with('nguoiDung')
            ->whereDate('ngay_lam', '>=', $fromDate->toDateString())
            ->whereDate('ngay_lam', '<=', $toDate->toDateString())
            ->orderBy('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        if ($shifts->isEmpty()) {
            return redirect()
                ->route('manager.shifts.index')
                ->with('error', 'Không có ca làm việc nào trong tuần tới để gửi.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lịch tuần tới');

        $headers = ['STT', 'Họ tên', 'Vai trò', 'Ca làm', 'Ngày làm', 'Giờ bắt đầu', 'Giờ kết thúc'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($shifts as $i => $shift) {
            $sheet->fromArray([
                $i + 1,
                $shift->nguoiDung?->ho_ten ?? '—',
                $shift->nguoiDung?->vai_tro ?? '—',
                $shift->ten_ca,
                Carbon::parse($shift->ngay_lam)->format('d/m/Y'),
                Carbon::parse($shift->gio_bat_dau)->format('H:i'),
                Carbon::parse($shift->gio_ket_thuc)->format('H:i'),
            ], null, "A{$rowIndex}");
            $rowIndex++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'schedule_next_week_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $userIds = $shifts->pluck('nguoi_dung_id')->unique()->values();
        $users = NguoiDung::whereIn('id', $userIds)
            ->whereNotNull('email')
            ->get();

        foreach ($users as $user) {
            Mail::to($user->email)->send(new NextWeekScheduleMail($user, $fromStr, $toStr, $tempFile));
            $user->notify(new ScheduleSentNotification($fromStr, $toStr));
        }

        @unlink($tempFile);

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Lịch làm tuần tới đã được gửi tới email của bạn.');
    }

    public function destroy(int $id)
    {
        $shift = CaLamViec::findOrFail($id);

        $shiftStart = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, (string) $shift->gio_bat_dau);
        $shiftEnd = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, (string) $shift->gio_ket_thuc);
        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        if (now()->greaterThanOrEqualTo($shiftStart)) {
            $label = now()->lessThanOrEqualTo($shiftEnd) ? 'đang diễn ra' : 'đã kết thúc';

            return redirect()
                ->route('manager.shifts.index')
                ->with('error', "Không thể xóa ca \"{$shift->ten_ca}\" vì ca này {$label}.");
        }

        $shiftData = [
            'ten_ca'      => $shift->ten_ca,
            'ngay_lam'    => $shift->ngay_lam,
            'gio_bat_dau' => Carbon::parse($shift->gio_bat_dau)->format('H:i'),
            'gio_ket_thuc' => Carbon::parse($shift->gio_ket_thuc)->format('H:i'),
        ];

        $ngayLamFormatted = Carbon::parse($shift->ngay_lam)->format('d/m/Y');

        $shiftGroup = $this->shiftService->buildShiftGroupQuery($shift)->get(['id', 'nguoi_dung_id']);
        $shiftIds   = $shiftGroup->pluck('id');
        $userIds    = $shiftGroup->pluck('nguoi_dung_id')->unique()->values();

        CaLamViec::query()
            ->whereIn('id', $shiftIds)
            ->delete();

        $users = NguoiDung::whereIn('id', $userIds)->whereNotNull('email')->get();
        foreach ($users as $user) {
            Mail::to($user->email)->send(new ShiftDeletedMail($user, $shiftData));
            $user->notify(new ShiftDeletedNotification($shift->ten_ca, $ngayLamFormatted));
        }

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Đã xóa ca làm việc thành công.');
    }

    // ── Attendance (Chấm công) ────────────────────────────────────────

    public function attendance(Request $request)
    {
        $today = now()->toDateString();
        $request->validate([
            'ngay' => ['nullable', 'date'],
            'nhan_vien' => ['nullable', 'string', 'max:150'],
            'ca_lam_viec_id' => ['nullable', 'integer'],
        ]);

        $selectedDate = (string) ($request->input('ngay') ?: $today);
        $employeeKeyword = trim((string) $request->input('nhan_vien', ''));
        $selectedShiftId = $request->input('ca_lam_viec_id');

        $shiftsForFilter = CaLamViec::with('nguoiDung.hoSoNhanVien')
            ->whereDate('ngay_lam', $selectedDate)
            ->orderBy('gio_bat_dau')
            ->get();

        $attendanceRecords = $this->buildAttendanceQuery($selectedDate, $employeeKeyword, $selectedShiftId)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('manager.shifts.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'shifts' => $shiftsForFilter,
            'shiftsForAttendance' => $shiftsForFilter,
            'selectedDate' => $selectedDate,
            'employeeKeyword' => $employeeKeyword,
            'selectedShiftId' => $selectedShiftId,
        ]);
    }

    public function storeAttendance(Request $request)
    {
        $validated = $request->validate([
            'ca_lam_viec_id' => ['required', 'integer', 'exists:ca_lam_viec,id'],
            'cham_cong_vao' => ['nullable', 'date'],
            'cham_cong_ra' => ['nullable', 'date', 'after:cham_cong_vao'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'ca_lam_viec_id.required' => 'Vui lòng chọn ca làm việc.',
            'cham_cong_ra.after' => 'Chấm công ra phải sau chấm công vào.',
        ]);

        $shift = CaLamViec::findOrFail($validated['ca_lam_viec_id']);

        ChamCong::updateOrCreate(
            [
                'ca_lam_viec_id' => $shift->id,
                'nguoi_dung_id' => $shift->nguoi_dung_id,
            ],
            [
                'cham_cong_vao' => $validated['cham_cong_vao'] ?? null,
                'cham_cong_ra' => $validated['cham_cong_ra'] ?? null,
                'ghi_chu' => $validated['ghi_chu'] ?? null,
            ]
        );

        return redirect()
            ->route('manager.shifts.attendance', $this->attendanceFilterParams($request))
            ->with('success', 'Đã lưu chấm công.');
    }

    public function updateAttendance(Request $request, int $id)
    {
        $attendance = ChamCong::findOrFail($id);

        $validated = $request->validate([
            'cham_cong_vao' => ['nullable', 'date'],
            'cham_cong_ra' => ['nullable', 'date', 'after:cham_cong_vao'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'cham_cong_ra.after' => 'Chấm công ra phải sau chấm công vào.',
        ]);

        $attendance->update([
            'cham_cong_vao' => $validated['cham_cong_vao'] ?? null,
            'cham_cong_ra' => $validated['cham_cong_ra'] ?? null,
            'ghi_chu' => $validated['ghi_chu'] ?? null,
        ]);

        return redirect()
            ->route('manager.shifts.attendance', $this->attendanceFilterParams($request))
            ->with('success', 'Đã cập nhật chấm công.');
    }

    public function destroyAttendance(Request $request, int $id)
    {
        ChamCong::findOrFail($id)->delete();

        return redirect()
            ->route('manager.shifts.attendance', $this->attendanceFilterParams($request))
            ->with('success', 'Đã xóa bản ghi chấm công.');
    }

    public function exportPayroll(Request $request)
    {
        $today = now()->toDateString();
        $selectedDate = (string) ($request->input('ngay') ?: $today);
        $employeeKeyword = trim((string) $request->input('nhan_vien', ''));
        $selectedShiftId = $request->input('ca_lam_viec_id');

        $records = $this->buildAttendanceQuery($selectedDate, $employeeKeyword, $selectedShiftId)
            ->orderBy('ca_lam_viec_id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bang cham cong');

        $headers = ['STT', 'Ngày làm', 'Nhân viên', 'Mã NV', 'Ca làm việc', 'Chấm công vào', 'Chấm công ra', 'Số giờ', 'Ghi chú'];
        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;
        foreach ($records as $i => $record) {
            $shift = $record->caLamViec;
            $user = $record->nguoiDung ?? $shift?->nguoiDung;
            $checkIn = $record->cham_cong_vao ? Carbon::parse($record->cham_cong_vao) : null;
            $checkOut = $record->cham_cong_ra ? Carbon::parse($record->cham_cong_ra) : null;

            if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
                $hours = round($checkIn->diffInMinutes($checkOut) / 60, 2);
            } elseif ($shift) {
                $hours = $this->shiftService->shiftDurationHours($shift);
            } else {
                $hours = 0;
            }

            $sheet->fromArray([
                $i + 1,
                $shift?->ngay_lam ? Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—',
                $user?->ho_ten ?? $user?->hoSoNhanVien?->ho_ten ?? '—',
                $user?->hoSoNhanVien?->ma_nhan_vien ?? '—',
                $shift
                    ? $shift->ten_ca . ' (' . Carbon::parse($shift->gio_bat_dau)->format('H:i') . '-' . Carbon::parse($shift->gio_ket_thuc)->format('H:i') . ')'
                    : '—',
                $checkIn ? $checkIn->format('d/m/Y H:i') : '—',
                $checkOut ? $checkOut->format('d/m/Y H:i') : '—',
                $hours,
                (string) ($record->ghi_chu ?? ''),
            ], null, 'A' . $rowIndex);
            $rowIndex++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'bang-cham-cong-' . $selectedDate . '-' . now()->format('His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'payroll_excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildAttendanceQuery(string $selectedDate, string $employeeKeyword, $selectedShiftId): Builder
    {
        return ChamCong::query()
            ->with(['caLamViec.nguoiDung.hoSoNhanVien', 'nguoiDung.hoSoNhanVien'])
            ->whereHas('caLamViec', function (Builder $query) use ($selectedDate) {
                $query->whereDate('ngay_lam', $selectedDate);
            })
            ->when($employeeKeyword !== '', function (Builder $query) use ($employeeKeyword) {
                $query->whereHas('nguoiDung', function (Builder $userQuery) use ($employeeKeyword) {
                    $userQuery->where('ho_ten', 'like', "%{$employeeKeyword}%")
                        ->orWhereHas('hoSoNhanVien', function (Builder $profileQuery) use ($employeeKeyword) {
                            $profileQuery->where('ho_ten', 'like', "%{$employeeKeyword}%");
                        });
                });
            })
            ->when(!empty($selectedShiftId), function (Builder $query) use ($selectedShiftId) {
                $query->where('ca_lam_viec_id', $selectedShiftId);
            });
    }

    private function attendanceFilterParams(Request $request): array
    {
        return array_filter([
            'ngay' => $request->input('ngay'),
            'nhan_vien' => $request->input('nhan_vien'),
            'ca_lam_viec_id' => $request->input('ca_lam_viec_id'),
        ], static fn ($value) => $value !== null && $value !== '');
    }
}