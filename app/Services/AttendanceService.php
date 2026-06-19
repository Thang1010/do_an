<?php

namespace App\Services;

use App\Exceptions\AttendanceException;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\CuaHang;
use App\Models\NguoiDung;
use App\Notifications\ShiftCheckoutNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Service chấm công dùng chung cho luồng QR (Manager\ShiftController) và luồng
 * nút bấm (Staff\ShiftController).
 *
 * Mục tiêu:
 * - Một bản ghi ChamCong duy nhất cho mỗi (nguoi_dung_id, ca_lam_viec_id).
 * - Kiểm tra khung giờ ca, khoảng tối thiểu giữa vào/ra, và vị trí GPS.
 * - Ghi chú lệch giờ + gửi notification check-out thống nhất.
 */
class AttendanceService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly GeocodingService $geocodingService,
    ) {}

    /**
     * Lấy bản ghi chấm công (1 dòng/ca/người), tạo mới nếu chưa có.
     */
    public function attendanceFor(int $userId, int $shiftId): ChamCong
    {
        return ChamCong::firstOrNew([
            'nguoi_dung_id' => $userId,
            'ca_lam_viec_id' => $shiftId,
        ]);
    }

    /**
     * Trạng thái hiện tại của bản ghi: 'checkin' | 'checkout' | 'done'.
     */
    public function nextAction(ChamCong $attendance): string
    {
        if ($attendance->cham_cong_vao && $attendance->cham_cong_ra) {
            return 'done';
        }

        return $attendance->cham_cong_vao ? 'checkout' : 'checkin';
    }

    // ── Geofencing ──────────────────────────────────────────────────────

    /**
     * Toạ độ quán suy ra từ địa chỉ (geocode + cache). Null nếu không tra được.
     */
    public function storeCoords(?CuaHang $store): ?array
    {
        return $store ? $this->geocodingService->geocode($store->dia_chi) : null;
    }

    /**
     * Geofencing có thực sự được bắt buộc không (bật + quán có toạ độ geocode được).
     */
    public function geoEnforced(?CuaHang $store): bool
    {
        return (bool) config('attendance.geo.enabled')
            && $this->storeCoords($store) !== null;
    }

    /**
     * Khoảng cách Haversine giữa 2 điểm (mét).
     */
    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0; // mét

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Bắt buộc người chấm công phải ở trong bán kính quán. Bỏ qua nếu geo tắt
     * hoặc quán chưa có toạ độ geocode được.
     */
    public function assertWithinStore(?CuaHang $store, ?float $lat, ?float $lng): void
    {
        $coords = $this->geoEnforced($store) ? $this->storeCoords($store) : null;
        if ($coords === null) {
            return;
        }

        if ($lat === null || $lng === null) {
            // Client chỉ gửi lên khi đã có toạ độ; rơi vào đây nghĩa là vị trí
            // chưa được gửi (chưa bấm "Cho phép vị trí"). Không quy kết GPS.
            throw new AttendanceException(
                'Chưa nhận được vị trí của bạn. Vui lòng bấm "Cho phép vị trí" rồi thử lại.'
            );
        }

        $radius = (int) config('attendance.geo.radius_meters');

        $distance = $this->distanceMeters($coords['lat'], $coords['lng'], $lat, $lng);

        if ($distance > $radius) {
            throw new AttendanceException(sprintf(
                'Bạn đang ở cách quán khoảng %d m (giới hạn %d m). Vui lòng chấm công khi đã có mặt tại quán.',
                (int) round($distance),
                $radius
            ));
        }
    }

    // ── Khung giờ ───────────────────────────────────────────────────────

    /**
     * [start, end] thực tế của ca (xử lý ca qua đêm).
     */
    public function shiftWindow(CaLamViec $shift): array
    {
        $start = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, (string) $shift->gio_bat_dau);
        $end = $this->shiftService->resolveShiftDateTime($shift->ngay_lam, (string) $shift->gio_ket_thuc);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * Cho phép check-in trong khoảng [start - early, end] (hoặc sau end nếu cấu hình).
     */
    public function assertCheckinWindow(CaLamViec $shift, CarbonInterface $now): void
    {
        [$start, $end] = $this->shiftWindow($shift);
        $earliest = $start->copy()->subMinutes((int) config('attendance.checkin_early_minutes'));

        if ($now->lessThan($earliest)) {
            throw new AttendanceException(sprintf(
                'Chưa đến giờ chấm công. Ca bắt đầu lúc %s, bạn chỉ có thể chấm công vào từ %s.',
                $start->format('H:i'),
                $earliest->format('H:i')
            ));
        }

        if (! config('attendance.checkin_after_end') && $now->greaterThan($end)) {
            throw new AttendanceException(sprintf(
                'Ca đã kết thúc lúc %s, không thể chấm công vào nữa. Vui lòng liên hệ quản lý.',
                $end->format('H:i')
            ));
        }
    }

    // ── Ghi chú lệch giờ ────────────────────────────────────────────────

    /**
     * Ghi chú lệch giờ vào/ra so với kế hoạch (dùng cho cả vào và ra).
     */
    public function buildDeviationNote(CaLamViec $shift, ChamCong $attendance): string
    {
        [$plannedStart, $plannedEnd] = $this->shiftWindow($shift);
        $checkIn = $attendance->cham_cong_vao ? Carbon::parse($attendance->cham_cong_vao) : null;
        $checkOut = $attendance->cham_cong_ra ? Carbon::parse($attendance->cham_cong_ra) : null;

        $parts = [];
        if ($checkIn) {
            $parts[] = $this->shiftService->buildTimeDeviationNote($checkIn, $plannedStart, 'vào ca');
        }
        if ($checkOut) {
            $parts[] = $this->shiftService->buildTimeDeviationNote($checkOut, $plannedEnd, 'ra ca');
        }

        return implode(' | ', array_filter($parts));
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'qr' => 'bằng QR',
            'manual' => 'thủ công',
            'manager' => 'do quản lý',
            default => '',
        };
    }

    private function appendNote(?string $existing, string $addition): string
    {
        $existing = trim((string) $existing);

        return $existing !== '' ? $existing . ' | ' . $addition : $addition;
    }

    // ── Thao tác chấm công ──────────────────────────────────────────────

    /**
     * Ghi nhận check-in. Trả về ChamCong đã lưu.
     */
    public function checkIn(NguoiDung $user, CaLamViec $assignedShift, string $source, ?CarbonInterface $when = null): ChamCong
    {
        $now = $when ? Carbon::parse($when) : now();
        $attendance = $this->attendanceFor((int) $user->id, (int) $assignedShift->id);

        if ($attendance->cham_cong_vao) {
            throw new AttendanceException('Bạn đã chấm công vào ca này rồi.', 'info');
        }

        $attendance->cham_cong_vao = $now;
        $label = $this->sourceLabel($source);
        $note = trim('Check-in ' . $label) . ' lúc ' . $now->format('H:i d/m/Y') . '.';
        $deviation = $this->shiftService->buildTimeDeviationNote(
            $now,
            $this->shiftWindow($assignedShift)[0],
            'vào ca'
        );
        $attendance->ghi_chu = $this->appendNote($attendance->ghi_chu, trim($deviation . ($deviation ? ' | ' : '') . $note));
        $attendance->save();

        return $attendance;
    }

    /**
     * Ghi nhận check-out + gửi notification. Trả về ChamCong đã lưu.
     */
    public function checkOut(NguoiDung $user, CaLamViec $assignedShift, string $source, ?CarbonInterface $when = null): ChamCong
    {
        $now = $when ? Carbon::parse($when) : now();
        $attendance = $this->attendanceFor((int) $user->id, (int) $assignedShift->id);

        if (! $attendance->cham_cong_vao) {
            throw new AttendanceException('Bạn chưa chấm công vào nên không thể chấm công ra.');
        }

        if ($attendance->cham_cong_ra) {
            throw new AttendanceException('Bạn đã hoàn thành chấm công ca này.', 'info');
        }

        $minMinutes = (int) config('attendance.min_work_minutes');
        $checkIn = Carbon::parse($attendance->cham_cong_vao);
        // Dùng chênh lệch tuyệt đối theo phút (Carbon 3 trả diff có dấu).
        $elapsedMinutes = abs($now->diffInMinutes($checkIn));
        if ($elapsedMinutes < $minMinutes) {
            throw new AttendanceException(sprintf(
                'Vừa mới chấm công vào. Vui lòng đợi ít nhất %d phút trước khi chấm công ra.',
                max(1, $minMinutes)
            ));
        }

        $attendance->cham_cong_ra = $now;
        $label = $this->sourceLabel($source);
        $note = trim('Check-out ' . $label) . ' lúc ' . $now->format('H:i d/m/Y') . '.';
        $attendance->ghi_chu = $this->appendNote($attendance->ghi_chu, $note);
        $attendance->save();

        $deviation = $this->buildDeviationNote($assignedShift, $attendance);
        $user->notify(new ShiftCheckoutNotification($assignedShift, $deviation));

        return $attendance;
    }
}
