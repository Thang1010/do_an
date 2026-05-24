<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\AutoScheduleShiftRequest;
use App\Http\Requests\Manager\StoreShiftRequest;
use App\Models\BangLuong;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\CuaHang;
use App\Models\NguoiDung;
use App\Notifications\ShiftAssignedNotification;
use App\Services\ShiftService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        return view('manager.shifts.create', $this->shiftService->buildShiftAssignmentData());
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
        $addMode = (string) $request->input('add_mode', 'manual');
        if ($addMode === 'auto_schedule') {
            return $this->storeAutoSchedule($request);
        }

        return $this->storeManual($request);
    }

    private function storeManual(Request $request)
    {
        $formRequest = new StoreShiftRequest();
        $validatedShift = $request->validate($formRequest->rules(), $formRequest->messages());

        $assignmentMode = (string) $request->input('assignment_mode', 'manual');
        if ($request->filled('nguoi_dung_id') && !in_array($assignmentMode, ['single', 'manual', 'auto'], true)) {
            $assignmentMode = 'single';
        }

        $selectedUserIds = $this->resolveAssignedUserIds($request, $assignmentMode)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedUserIds->isEmpty()) {
            throw ValidationException::withMessages([
                'assignment_mode' => 'Vui lòng chọn ít nhất một nhân sự để phân ca.',
            ]);
        }

        $validUserIds = NguoiDung::query()
            ->whereIn('id', $selectedUserIds)
            ->where('trang_thai', 'hoạt động')
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->pluck('id');

        if ($validUserIds->isEmpty()) {
            throw ValidationException::withMessages([
                'assignment_mode' => 'Danh sách nhân sự được chọn không hợp lệ hoặc không ở trạng thái hoạt động.',
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
        $this->notifyShiftAssignments($validUserIds, $validatedShift);

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Đã thêm ca làm việc cho ' . $validUserIds->count() . ' nhân sự.');
    }

    private function resolveAssignedUserIds(Request $request, string $assignmentMode): Collection
    {
        if ($assignmentMode === 'single') {
            $request->merge([
                'nguoi_dung_id' => collect($request->input('nguoi_dung_id'))
                    ->filter(fn ($id) => is_scalar($id) && trim((string) $id) !== '')
                    ->values()
                    ->all(),
            ]);

            $validated = $request->validate([
                'nguoi_dung_id' => ['required', 'array'],
                'nguoi_dung_id.*' => ['integer', 'exists:nguoi_dung,id'],
            ]);

            return collect($validated['nguoi_dung_id'] ?? []);
        }

        if ($assignmentMode === 'auto') {
            $validated = $request->validate([
                'auto_use_manager_count' => ['nullable', 'in:1'],
                'auto_use_position_counts' => ['nullable', 'in:1'],
                'manager_count' => ['nullable', 'integer', 'min:0'],
                'position_counts' => ['nullable', 'array'],
                'position_counts.*' => ['nullable', 'integer', 'min:0'],
                'position_labels' => ['nullable', 'array'],
                'position_labels.*' => ['nullable', 'string', 'max:100'],
            ]);

            $useManagerCount = ($validated['auto_use_manager_count'] ?? null) === '1';
            $usePositionCounts = ($validated['auto_use_position_counts'] ?? null) === '1';

            $managerCount = $useManagerCount
                ? (int) ($validated['manager_count'] ?? 0)
                : 0;

            $positionCounts = collect($validated['position_counts'] ?? [])
                ->map(fn ($count) => (int) $count)
                ->filter(fn (int $count) => $count > 0);

            if (! $usePositionCounts) {
                $positionCounts = collect();
            }

            $positionLabels = collect($validated['position_labels'] ?? [])
                ->map(fn ($label) => trim((string) $label));

            try {
                return $this->shiftService->resolveAutoAssignedUserIdsByCounts(
                    $managerCount,
                    $positionCounts,
                    (string) $request->input('gio_bat_dau'),
                    (string) $request->input('gio_ket_thuc'),
                    $positionLabels,
                    (string) $request->input('ngay_lam'),
                    collect()
                );
            } catch (ValidationException $exception) {
                $mappedErrors = [];
                foreach ($exception->errors() as $key => $messages) {
                    if (str_starts_with($key, 'auto_manager_count')) {
                        $mappedErrors['manager_count'] = $messages;
                        continue;
                    }

                    if (str_starts_with($key, 'auto_position_counts.')) {
                        $mappedKey = 'position_counts.' . substr($key, strlen('auto_position_counts.'));
                        $mappedErrors[$mappedKey] = $messages;
                        continue;
                    }

                    if (str_starts_with($key, 'auto_position_counts')) {
                        $mappedErrors['position_counts'] = $messages;
                        continue;
                    }

                    $mappedErrors[$key] = $messages;
                }

                throw ValidationException::withMessages($mappedErrors);
            }
        }

        $validated = $request->validate([
            'selected_manager_ids' => ['nullable', 'array'],
            'selected_manager_ids.*' => ['integer', 'exists:nguoi_dung,id'],
            'selected_staff_ids' => ['nullable', 'array'],
            'selected_staff_ids.*' => ['integer', 'exists:nguoi_dung,id'],
        ]);

        return collect($validated['selected_manager_ids'] ?? [])
            ->merge($validated['selected_staff_ids'] ?? [])
            ->values();
    }

    private function storeAutoSchedule(AutoScheduleShiftRequest $request)
    {
        $validated = $request->validated();

        $managerCount = (int) ($validated['auto_manager_count'] ?? 0);
        $positionCounts = collect($validated['auto_position_counts'] ?? [])
            ->map(fn ($count) => (int) $count)
            ->filter(fn (int $count) => $count > 0);
        $positionLabels = collect($validated['auto_position_labels'] ?? [])
            ->map(fn ($label) => trim((string) $label));

        $staffPerShift = $managerCount + $positionCounts->sum();
        if ($staffPerShift < 3) {
            throw ValidationException::withMessages([
                'auto_manager_count' => 'Mỗi ca tự động cần ít nhất 3 nhân sự (admin + nhân viên).',
            ]);
        }

        $fromDate = Carbon::parse((string) $validated['date_from'])->startOfDay();
        $toDate = Carbon::parse((string) $validated['date_to'])->startOfDay();
        $shiftTemplates = $this->shiftService->buildDailyShiftTemplates((int) $validated['shifts_per_day']);

        $rows = collect();
        $timestamp = now();
        $dateCursor = $fromDate->copy();

        while ($dateCursor->lessThanOrEqualTo($toDate)) {
            $dateString = $dateCursor->format('Y-m-d');

            foreach ($shiftTemplates as $template) {
                $assignedUserIds = $this->shiftService->resolveAutoAssignedUserIdsByCounts(
                    $managerCount,
                    $positionCounts,
                    $template['gio_bat_dau'],
                    $template['gio_ket_thuc'],
                    $positionLabels,
                    $dateString,
                    $rows
                );

                foreach ($assignedUserIds as $userId) {
                    $rows->push([
                        'nguoi_dung_id' => (int) $userId,
                        'ten_ca' => $template['ten_ca'],
                        'ngay_lam' => $dateString,
                        'gio_bat_dau' => $template['gio_bat_dau'],
                        'gio_ket_thuc' => $template['gio_ket_thuc'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            }

            $dateCursor->addDay();
        }

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'date_from' => 'Không có dữ liệu ca nào được tạo. Vui lòng kiểm tra lại thông tin cấu hình.',
            ]);
        }

        CaLamViec::query()->insert($rows->all());
        $this->notifyAutoShiftAssignments($rows, $fromDate, $toDate, $timestamp);

        $totalDays = $fromDate->diffInDays($toDate) + 1;
        $totalShiftGroups = $totalDays * count($shiftTemplates);

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Đã tạo tự động ' . $totalShiftGroups . ' ca trong ' . $totalDays . ' ngày, tổng ' . $rows->count() . ' phân công nhân sự.');
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

        $shiftPayload = [
            'ten_ca' => $shift->ten_ca,
            'ngay_lam' => $shift->ngay_lam ? Carbon::parse($shift->ngay_lam)->format('Y-m-d') : now()->toDateString(),
            'gio_bat_dau' => Carbon::parse($shift->gio_bat_dau)->format('H:i'),
            'gio_ket_thuc' => Carbon::parse($shift->gio_ket_thuc)->format('H:i'),
        ];

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
            $validatedMembers = $request->validate([
                'selected_manager_ids' => ['nullable', 'array'],
                'selected_manager_ids.*' => ['integer', 'exists:nguoi_dung,id'],
                'selected_staff_ids' => ['nullable', 'array'],
                'selected_staff_ids.*' => ['integer', 'exists:nguoi_dung,id'],
            ]);

            $selectedUserIds = collect($validatedMembers['selected_manager_ids'] ?? [])
                ->merge($validatedMembers['selected_staff_ids'] ?? [])
                ->map(fn ($userId) => (int) $userId)
                ->unique()
                ->values();

            if ($selectedUserIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'assignment_mode' => 'Vui lòng chọn ít nhất một nhân sự cho ca làm.',
                ]);
            }

            $validUserIds = NguoiDung::query()
                ->whereIn('id', $selectedUserIds)
                ->where('trang_thai', 'hoạt động')
                ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
                ->pluck('id')
                ->map(fn ($userId) => (int) $userId)
                ->values();

            if ($validUserIds->count() !== $selectedUserIds->count()) {
                throw ValidationException::withMessages([
                    'assignment_mode' => 'Danh sách nhân sự chọn có tài khoản không hợp lệ hoặc không ở trạng thái hoạt động.',
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
            ChamCong::query()
                ->whereIn('ca_lam_viec_id', $shiftIdsToRemove)
                ->delete();

            CaLamViec::query()
                ->whereIn('id', $shiftIdsToRemove)
                ->delete();
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
            $this->notifyShiftAssignments($usersToAdd, $shiftPayload);
        }

        if ($editMode === 'info') {
            return redirect()
                ->route('manager.shifts.show', $shift->id)
                ->with('success', 'Đã cập nhật thông tin ca làm việc.');
        }

        if ($editMode === 'staff') {
            return redirect()
                ->route('manager.shifts.show', $shift->id)
                ->with('success', 'Đã cập nhật danh sách nhân sự trong ca.');
        }

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', 'Đã cập nhật đầy đủ ca làm và nhân sự cho ' . $targetUserIds->count() . ' người.');
    }

    public function show(int $id)
    {
        $shift = CaLamViec::with(['nguoiDung.hoSoNhanVien'])->findOrFail($id);
        $checkinQrUrl = URL::temporarySignedRoute(
            'shifts.checkin.scan',
            now()->addHours(12),
            ['id' => $shift->id]
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
            $checkIn = $attendance?->check_in_luc ? Carbon::parse($attendance->check_in_luc) : null;
            $checkOut = $attendance?->check_out_luc ? Carbon::parse($attendance->check_out_luc) : null;

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
                $this->shiftService->buildTimeDeviationNote($checkIn, $plannedStart, 'check in'),
                $this->shiftService->buildTimeDeviationNote($checkOut, $plannedEnd, 'check out'),
                $attendance?->ghi_chu,
            ])
                ->map(fn ($note) => trim((string) $note))
                ->filter()
                ->values();

            return [
                'nguoi_dung_id' => (int) $groupShift->nguoi_dung_id,
                'ca_lam_viec_id' => (int) $groupShift->id,
                'email' => $groupShift->nguoiDung?->email,
                'nhan_su' => $groupShift->nguoiDung?->ho_ten,
                'vai_tro' => $groupShift->nguoiDung?->vai_tro ?? '—',
                'ma_nhan_vien' => $groupShift->nguoiDung?->hoSoNhanVien?->ma_nhan_vien,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'so_gio' => $workedHours,
                'ghi_chu' => $noteParts->isNotEmpty() ? $noteParts->implode(' | ') : 'Chưa có ghi chú',
                'can_force_checkout' => $checkOut === null,
            ];
        });

        return view('manager.shifts.show', [
            'shift' => $shift,
            'memberDetails' => $memberDetails,
            'shiftDurationHours' => $shiftDurationHours,
            'totalAssignedUsers' => $shiftGroup->count(),
            'checkinQrUrl' => $checkinQrUrl,
        ]);
    }

    public function scanCheckIn(Request $request, int $id)
    {
        $actor = Auth::guard('nguoi_dung')->user() ?? $request->user();
        if (! $actor) {
            abort(403, 'Bạn cần đăng nhập để chấm công bằng QR.');
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

        if ($attendance->check_in_luc) {
            return redirect()
                ->route($this->resolveCheckinRedirectRoute($actor))
                ->with('info', 'Bạn đã check-in ca này trước đó.');
        }

        $attendance->check_in_luc = now();
        $attendance->ghi_chu = trim((string) $attendance->ghi_chu) === ''
            ? 'Check-in bằng QR.'
            : $attendance->ghi_chu;
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

    public function forceCheckout(Request $request, int $id, int $userId)
    {
        $shift = CaLamViec::findOrFail($id);

        $targetShift = $this->shiftService->buildShiftGroupQuery($shift)
            ->where('nguoi_dung_id', $userId)
            ->firstOrFail();

        $plannedStart = $this->shiftService->resolveShiftDateTime($targetShift->ngay_lam, (string) $targetShift->gio_bat_dau);
        $plannedEnd = $this->shiftService->resolveShiftDateTime($targetShift->ngay_lam, (string) $targetShift->gio_ket_thuc);
        if ($plannedEnd->lessThanOrEqualTo($plannedStart)) {
            $plannedEnd->addDay();
        }

        $attendance = ChamCong::firstOrNew([
            'nguoi_dung_id' => $userId,
            'ca_lam_viec_id' => $targetShift->id,
        ]);

        if ($attendance->check_out_luc) {
            return redirect()
                ->route('manager.shifts.show', $id)
                ->with('success', 'Nhân sự này đã có thời gian kết ca.');
        }

        $resolvedCheckIn = $attendance->check_in_luc
            ? Carbon::parse($attendance->check_in_luc)
            : $plannedStart->copy();

        $resolvedCheckOut = $plannedEnd->copy();
        if ($resolvedCheckOut->lessThan($resolvedCheckIn)) {
            $resolvedCheckOut = $resolvedCheckIn->copy();
        }

        $autoNote = 'Quản lý kết thúc ca hộ tự động lúc ' . $resolvedCheckOut->format('d/m/Y H:i');
        $existingNote = trim((string) ($attendance->ghi_chu ?? ''));

        $attendance->check_in_luc = $attendance->check_in_luc ?? $resolvedCheckIn;
        $attendance->check_out_luc = $resolvedCheckOut;
        $attendance->ghi_chu = $existingNote !== ''
            ? ($existingNote . ' | ' . $autoNote)
            : $autoNote;
        $attendance->save();

        return redirect()
            ->route('manager.shifts.show', $id)
            ->with('success', 'Đã kết thúc ca hộ cho nhân sự.');
    }

    public function destroy(int $id)
    {
        $shift = CaLamViec::findOrFail($id);
        $shiftName = $shift->ten_ca;

        $shiftIds = $this->shiftService->buildShiftGroupQuery($shift)->pluck('id');

        ChamCong::query()
            ->whereIn('ca_lam_viec_id', $shiftIds)
            ->delete();

        CaLamViec::query()
            ->whereIn('id', $shiftIds)
            ->delete();

        return redirect()
            ->route('manager.shifts.index')
            ->with('success', "Đã xóa ca {$shiftName} và toàn bộ nhân sự trong ca.");
    }

    public function attendance(Request $request)
    {
        $today = now()->toDateString();
        $selectedDate = $request->input('ngay', $today);
        $selectedShiftId = $request->filled('ca_lam_viec_id') ? (int) $request->input('ca_lam_viec_id') : null;
        $employeeKeyword = trim((string) $request->input('nhan_vien', ''));

        $attendanceQuery = ChamCong::query()->with([
            'nguoiDung.hoSoNhanVien',
            'caLamViec.nguoiDung',
        ]);

        $this->shiftService->applyAttendanceFilters($attendanceQuery, $selectedDate, $employeeKeyword, $selectedShiftId);

        $attendanceRecords = $attendanceQuery
            ->latest('check_in_luc')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $shifts = CaLamViec::query()
            ->with('nguoiDung')
            ->orderByDesc('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        $shiftsForAttendance = CaLamViec::query()
            ->with('nguoiDung.hoSoNhanVien')
            ->when($selectedDate, function (Builder $query) use ($selectedDate) {
                $query->whereDate('ngay_lam', $selectedDate);
            })
            ->when($selectedShiftId, function (Builder $query) use ($selectedShiftId) {
                $query->where('id', $selectedShiftId);
            })
            ->when($employeeKeyword !== '', function (Builder $query) use ($employeeKeyword) {
                $query->whereHas('nguoiDung', function (Builder $sub) use ($employeeKeyword) {
                    $sub->where('ho_ten', 'like', "%{$employeeKeyword}%");
                });
            })
            ->orderBy('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        return view('manager.shifts.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'shifts' => $shifts,
            'shiftsForAttendance' => $shiftsForAttendance,
            'selectedDate' => $selectedDate,
            'selectedShiftId' => $selectedShiftId,
            'employeeKeyword' => $employeeKeyword,
        ]);
    }

    public function storeAttendance(Request $request)
    {
        $validated = $request->validate([
            'ca_lam_viec_id' => ['required', 'integer', 'exists:ca_lam_viec,id'],
            'check_in_luc' => ['nullable', 'date'],
            'check_out_luc' => ['nullable', 'date', 'after_or_equal:check_in_luc'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        $shift = CaLamViec::findOrFail((int) $validated['ca_lam_viec_id']);

        $identity = [
            'nguoi_dung_id' => $shift->nguoi_dung_id,
            'ca_lam_viec_id' => $shift->id,
        ];

        $record = ChamCong::updateOrCreate(
            $identity,
            array_merge($identity, [
                'check_in_luc' => $validated['check_in_luc'] ?? null,
                'check_out_luc' => $validated['check_out_luc'] ?? null,
                'ghi_chu' => $validated['ghi_chu'] ?? null,
            ])
        );

        return redirect()
            ->route('manager.shifts.attendance', $request->only(['ngay', 'nhan_vien', 'ca_lam_viec_id']))
            ->with('success', 'Đã lưu chấm công.');
    }

    public function updateAttendance(Request $request, int $id)
    {
        $attendance = ChamCong::findOrFail($id);

        $validated = $request->validate([
            'check_in_luc' => ['nullable', 'date'],
            'check_out_luc' => ['nullable', 'date', 'after_or_equal:check_in_luc'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update([
            'check_in_luc' => $validated['check_in_luc'] ?? null,
            'check_out_luc' => $validated['check_out_luc'] ?? null,
            'ghi_chu' => $validated['ghi_chu'] ?? null,
        ]);

        return redirect()
            ->route('manager.shifts.attendance', $request->only(['ngay', 'nhan_vien', 'ca_lam_viec_id']))
            ->with('success', 'Đã cập nhật chấm công.');
    }

    public function destroyAttendance(Request $request, int $id)
    {
        $attendance = ChamCong::findOrFail($id);
        $attendance->delete();

        return redirect()
            ->route('manager.shifts.attendance', $request->only(['ngay', 'nhan_vien', 'ca_lam_viec_id']))
            ->with('success', 'Đã xóa bản ghi chấm công.');
    }

    public function exportPayroll(Request $request): StreamedResponse
    {
        $selectedDate = $request->input('ngay');
        $selectedShiftId = $request->filled('ca_lam_viec_id') ? (int) $request->input('ca_lam_viec_id') : null;
        $employeeKeyword = trim((string) $request->input('nhan_vien', ''));

        $query = ChamCong::query()->with([
            'nguoiDung.hoSoNhanVien',
            'caLamViec',
        ]);

        $this->shiftService->applyAttendanceFilters($query, $selectedDate, $employeeKeyword, $selectedShiftId);

        $records = $query
            ->orderBy('nguoi_dung_id')
            ->orderBy('check_in_luc')
            ->get();

        $grouped = $records->groupBy(function (ChamCong $record) {
            $periodDate = $record->check_in_luc
                ? Carbon::parse($record->check_in_luc)
                : ($record->caLamViec?->ngay_lam ? Carbon::parse($record->caLamViec->ngay_lam) : now());

            return $record->nguoi_dung_id . '-' . $periodDate->format('m-Y');
        });

        $fileName = 'bang-luong-theo-ca-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($grouped) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Ma nhan su',
                'Ho ten',
                'Thang',
                'Nam',
                'Tong so ca',
                'Tong so gio',
                'Luong co ban moi ca',
                'Luong tam tinh',
            ]);

            foreach ($grouped as $rows) {
                $first = $rows->first();
                if (!$first || !$first->nguoiDung) {
                    continue;
                }

                $user = $first->nguoiDung;
                $profile = $user->hoSoNhanVien;

                $periodDate = $first->check_in_luc
                    ? Carbon::parse($first->check_in_luc)
                    : ($first->caLamViec?->ngay_lam ? Carbon::parse($first->caLamViec->ngay_lam) : now());

                $month = (int) $periodDate->format('m');
                $year = (int) $periodDate->format('Y');

                $totalShifts = $rows->pluck('ca_lam_viec_id')->filter()->unique()->count();
                if ($totalShifts === 0) {
                    $totalShifts = $rows->count();
                }

                $totalHours = round($rows->sum(function (ChamCong $attendance) {
                    return $this->shiftService->attendanceHours($attendance);
                }), 2);

                $baseSalary = (float) ($profile->luong_co_ban ?? 0);
                $estimatedSalary = $totalShifts * $baseSalary;

                $payrollData = [
                    'tong_so_ca' => $totalShifts,
                    'tong_so_gio' => $totalHours,
                    'luong_co_ban' => $baseSalary,
                    'thuong' => 0,
                    'khau_tru' => 0,
                    'luong_thuc_nhan' => $estimatedSalary,
                    'trang_thai' => 'nháp',
                ];

                BangLuong::updateOrCreate(
                    [
                        'nguoi_dung_id' => $user->id,
                        'thang' => $month,
                        'nam' => $year,
                    ],
                    $payrollData
                );

                fputcsv($out, [
                    $profile->ma_nhan_vien ?? ('NV' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT)),
                    $user->ho_ten,
                    $month,
                    $year,
                    $totalShifts,
                    number_format($totalHours, 2, '.', ''),
                    number_format($baseSalary, 0, '.', ''),
                    number_format($estimatedSalary, 0, '.', ''),
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // applyAttendanceFilters() => Đã chuyển sang ShiftService::applyAttendanceFilters()

    private function notifyShiftAssignments(Collection $userIds, array $shiftPayload): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $staffIds = NguoiDung::query()
            ->whereIn('id', $userIds->all())
            ->where('vai_tro', 'nhân viên')
            ->where('trang_thai', 'hoạt động')
            ->pluck('id');

        if ($staffIds->isEmpty()) {
            return;
        }

        $shifts = CaLamViec::query()
            ->with('nguoiDung')
            ->whereIn('nguoi_dung_id', $staffIds)
            ->whereDate('ngay_lam', $shiftPayload['ngay_lam'])
            ->whereTime('gio_bat_dau', $shiftPayload['gio_bat_dau'])
            ->whereTime('gio_ket_thuc', $shiftPayload['gio_ket_thuc'])
            ->get();

        foreach ($shifts as $shift) {
            $shift->nguoiDung?->notify(new ShiftAssignedNotification($shift));
        }
    }

    private function notifyAutoShiftAssignments(Collection $rows, Carbon $fromDate, Carbon $toDate, Carbon $timestamp): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $staffIds = $rows
            ->pluck('nguoi_dung_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($staffIds->isEmpty()) {
            return;
        }

        $activeStaffIds = NguoiDung::query()
            ->whereIn('id', $staffIds)
            ->where('vai_tro', 'nhân viên')
            ->where('trang_thai', 'hoạt động')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($activeStaffIds->isEmpty()) {
            return;
        }

        $createdFrom = $timestamp->copy()->subSeconds(2);
        $createdTo = $timestamp->copy()->addSeconds(2);

        $shifts = CaLamViec::query()
            ->with('nguoiDung')
            ->whereIn('nguoi_dung_id', $activeStaffIds)
            ->whereDate('ngay_lam', '>=', $fromDate)
            ->whereDate('ngay_lam', '<=', $toDate)
            ->whereBetween('created_at', [$createdFrom, $createdTo])
            ->get();

        foreach ($shifts as $shift) {
            $shift->nguoiDung?->notify(new ShiftAssignedNotification($shift));
        }
    }


    // shiftDurationHours(), attendanceHours(), buildShiftAssignmentData(), etc.
    // => Moved to ShiftService
}
