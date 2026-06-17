<?php

namespace App\Services;

use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\NguoiDung;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Service xử lý nghiệp vụ ca làm việc, chấm công, bảng lương.
 *
 * Tách từ Manager\ShiftController để giảm kích thước controller.
 */
class ShiftService
{
    /**
     * Tính số giờ của một ca làm việc.
     */
    public function shiftDurationHours(CaLamViec $shift): float
    {
        $start = Carbon::createFromFormat('H:i:s', (string) $shift->gio_bat_dau);
        $end = Carbon::createFromFormat('H:i:s', (string) $shift->gio_ket_thuc);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    /**
     * Tính số giờ thực tế của bản chấm công.
     */
    public function attendanceHours(ChamCong $attendance): float
    {
        $checkIn = $attendance->cham_cong_vao ? Carbon::parse($attendance->cham_cong_vao) : null;
        $checkOut = $attendance->cham_cong_ra ? Carbon::parse($attendance->cham_cong_ra) : null;

        if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
            return round($checkIn->diffInMinutes($checkOut) / 60, 2);
        }

        $shift = $attendance->caLamViec;
        if ($shift) {
            return $this->shiftDurationHours($shift);
        }

        return 0.0;
    }

    /**
     * Áp dụng bộ lọc chấm công.
     */
    public function applyAttendanceFilters(Builder $query, ?string $date, string $employeeKeyword, ?int $shiftId): void
    {
        if (!empty($date)) {
            $query->whereHas('caLamViec', function (Builder $sub) use ($date) {
                $sub->whereDate('ngay_lam', $date);
            });
        }

        if ($employeeKeyword !== '') {
            $query->whereHas('nguoiDung', function (Builder $sub) use ($employeeKeyword) {
                $sub->where('ho_ten', 'like', "%{$employeeKeyword}%");
            });
        }

        if ($shiftId) {
            $query->where('ca_lam_viec_id', $shiftId);
        }
    }

    /**
     * Xây dựng ghi chú thời gian sai lệch (sớm/muộn).
     */
    public function formatMinutesToHours(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) {
            return "{$h} giờ {$m} phút";
        }
        if ($h > 0) {
            return "{$h} giờ";
        }
        return "{$m} phút";
    }

    public function buildTimeDeviationNote(?CarbonInterface $actualTime, CarbonInterface $plannedTime, string $label): string
    {
        if (!$actualTime) {
            return 'Chưa ' . $label;
        }

        $minuteDifference = (int) $actualTime->diffInMinutes($plannedTime, false);

        if ($minuteDifference === 0) {
            return ucfirst($label) . ' đúng giờ';
        }

        $formatted = $this->formatMinutesToHours(abs($minuteDifference));

        if ($minuteDifference > 0) {
            return ucfirst($label) . ' sớm ' . $formatted;
        }

        return ucfirst($label) . ' muộn ' . $formatted;
    }

    /**
     * Tạo query nhóm ca theo ngày + tên ca + giờ.
     */
    public function buildShiftGroupQuery(CaLamViec $shift): Builder
    {
        return CaLamViec::query()
            ->whereDate('ngay_lam', $shift->ngay_lam)
            ->where('ten_ca', $shift->ten_ca)
            ->whereTime('gio_bat_dau', $shift->gio_bat_dau)
            ->whereTime('gio_ket_thuc', $shift->gio_ket_thuc);
    }

    /**
     * Resolve datetime cho ca làm việc.
     */
    public function resolveShiftDateTime(mixed $shiftDate, string $shiftTime): Carbon
    {
        $date = $shiftDate instanceof Carbon
            ? $shiftDate->format('Y-m-d')
            : Carbon::parse((string) $shiftDate)->format('Y-m-d');

        return Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . Carbon::parse($shiftTime)->format('H:i:s'));
    }

    /**
     * Xây dựng interval [start, end] cho 1 ca.
     */
    public function buildShiftInterval(string $shiftDate, string $startTime, string $endTime): array
    {
        $start = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $shiftDate . ' ' . Carbon::parse($startTime)->format('H:i:s')
        );

        $end = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $shiftDate . ' ' . Carbon::parse($endTime)->format('H:i:s')
        );

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * Tìm xung đột giờ ca cho danh sách user.
     */
    public function findShiftTimeConflictForUsers(
        string $shiftDate,
        string $startTime,
        string $endTime,
        Collection $userIds,
        ?Collection $excludeShiftIds = null
    ): ?array {
        $userIds = $userIds
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return null;
        }

        [$newStart, $newEnd] = $this->buildShiftInterval($shiftDate, $startTime, $endTime);

        $query = CaLamViec::query()
            ->with(['nguoiDung.hoSoNhanVien', 'nguoiDung.hoSoQuanLy'])
            ->whereDate('ngay_lam', $shiftDate)
            ->whereIn('nguoi_dung_id', $userIds)
            ->orderBy('gio_bat_dau');

        if ($excludeShiftIds && $excludeShiftIds->isNotEmpty()) {
            $query->whereNotIn('id', $excludeShiftIds);
        }

        $existingShifts = $query->get([
            'id',
            'nguoi_dung_id',
            'ten_ca',
            'ngay_lam',
            'gio_bat_dau',
            'gio_ket_thuc',
        ]);

        foreach ($existingShifts as $existingShift) {
            [$existingStart, $existingEnd] = $this->buildShiftInterval(
                (string) Carbon::parse($existingShift->ngay_lam)->format('Y-m-d'),
                (string) $existingShift->gio_bat_dau,
                (string) $existingShift->gio_ket_thuc
            );

            $isOverlapping = $newStart->lt($existingEnd) && $existingStart->lt($newEnd);
            if (!$isOverlapping) {
                continue;
            }

            return [
                'shift_name' => (string) ($existingShift->ten_ca ?: 'không tên'),
                'user_name' => (string) ($existingShift->nguoiDung?->ho_ten ?: 'nhân sự không xác định'),
                'existing_start' => $existingStart->format('H:i'),
                'existing_end' => $existingEnd->format('H:i'),
            ];
        }

        return null;
    }

    /**
     * Lọc user khả dụng cho 1 slot ca (không trùng giờ).
     */
    public function filterAvailableUsersForSlot(
        Collection $candidateUserIds,
        string $shiftDate,
        string $startTime,
        string $endTime,
        Collection $pendingRows,
        ?Collection $excludeShiftIds = null
    ): Collection {
        $candidateUserIds = $candidateUserIds
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($candidateUserIds->isEmpty()) {
            return $candidateUserIds;
        }

        [$slotStart, $slotEnd] = $this->buildShiftInterval($shiftDate, $startTime, $endTime);

        $busyIds = collect();

        $existingShifts = CaLamViec::query()
            ->whereDate('ngay_lam', $shiftDate)
            ->whereIn('nguoi_dung_id', $candidateUserIds)
            ->when($excludeShiftIds && $excludeShiftIds->isNotEmpty(), function ($query) use ($excludeShiftIds) {
                $query->whereNotIn('id', $excludeShiftIds);
            })
            ->get(['id', 'nguoi_dung_id', 'ngay_lam', 'gio_bat_dau', 'gio_ket_thuc']);

        foreach ($existingShifts as $existingShift) {
            [$existingStart, $existingEnd] = $this->buildShiftInterval(
                Carbon::parse((string) $existingShift->ngay_lam)->format('Y-m-d'),
                (string) $existingShift->gio_bat_dau,
                (string) $existingShift->gio_ket_thuc
            );

            if ($slotStart->lt($existingEnd) && $existingStart->lt($slotEnd)) {
                $busyIds->push((int) $existingShift->nguoi_dung_id);
            }
        }

        foreach ($pendingRows as $pendingRow) {
            $pendingDate = (string) ($pendingRow['ngay_lam'] ?? '');
            if ($pendingDate !== $shiftDate) {
                continue;
            }

            $pendingUserId = (int) ($pendingRow['nguoi_dung_id'] ?? 0);
            if (!$candidateUserIds->contains($pendingUserId)) {
                continue;
            }

            [$pendingStart, $pendingEnd] = $this->buildShiftInterval(
                $pendingDate,
                (string) ($pendingRow['gio_bat_dau'] ?? '00:00'),
                (string) ($pendingRow['gio_ket_thuc'] ?? '00:00')
            );

            if ($slotStart->lt($pendingEnd) && $pendingStart->lt($slotEnd)) {
                $busyIds->push($pendingUserId);
            }
        }

        return $candidateUserIds
            ->diff($busyIds->unique()->values())
            ->values();
    }

    /**
     * Lấy tên chức vụ của nhân viên.
     */
    public function resolveEmployeePositionName(NguoiDung $user): string
    {
        $positionName = trim((string) ($user->hoSoNhanVien?->chucVu?->ten_chuc_vu ?? ''));
        if ($positionName !== '') {
            return $positionName;
        }

        return 'Chưa gán chức vụ';
    }

    /**
     * Xây dựng dữ liệu phân ca (manager users, staff users by position).
     */
    public function buildShiftAssignmentData(): array
    {
        $workingUsers = NguoiDung::query()
            ->with(['hoSoNhanVien.chucVu', 'hoSoQuanLy'])
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->get(['id', 'email', 'vai_tro', 'trang_thai'])
            ->sortBy('ho_ten', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $managerUsers = $workingUsers
            ->where('vai_tro', 'quản lý')
            ->where('trang_thai', 'hoạt động')
            ->values();

        $staffUsers = $workingUsers
            ->filter(function (NguoiDung $user) {
                return $user->vai_tro === 'nhân viên'
                    && $user->trang_thai === 'hoạt động'
                    && $user->hoSoNhanVien;
            })
            ->values();

        $staffUsersByPosition = $staffUsers
            ->groupBy(fn (NguoiDung $user) => $this->resolveEmployeePositionName($user))
            ->sortKeys();

        $staffPositions = $staffUsersByPosition->keys()->values();

        return [
            'managerUsers' => $managerUsers,
            'staffUsers' => $staffUsers,
            'staffPositions' => $staffPositions,
            'staffUsersByPosition' => $staffUsersByPosition,
        ];
    }

}
