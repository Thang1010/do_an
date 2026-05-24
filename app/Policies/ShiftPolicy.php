<?php

namespace App\Policies;

use App\Models\CaLamViec;
use App\Models\NguoiDung;

/**
 * Policy kiểm soát quyền truy cập ca làm việc và chấm công.
 *
 * Tập trung logic phân quyền từ ShiftController.
 */
class ShiftPolicy
{
    /**
     * Chỉnh sửa / xóa ca: chỉ manager hoặc chủ cửa hàng.
     */
    public function update(NguoiDung $user, CaLamViec $shift): bool
    {
        return in_array($user->vai_tro, ['quản lý', 'chủ cửa hàng'], true);
    }

    public function delete(NguoiDung $user, CaLamViec $shift): bool
    {
        return in_array($user->vai_tro, ['quản lý', 'chủ cửa hàng'], true);
    }

    /**
     * Xem chi tiết ca / danh sách chấm công.
     */
    public function view(NguoiDung $user, CaLamViec $shift): bool
    {
        return in_array($user->vai_tro, ['nhân viên', 'quản lý', 'chủ cửa hàng'], true);
    }

    /**
     * Check-in bằng QR: phải thuộc danh sách nhân sự của ca.
     * (Kiểm tra chi tiết còn phụ thuộc vào dữ liệu shift, thực hiện trong controller)
     */
    public function scanCheckIn(NguoiDung $user): bool
    {
        return in_array($user->vai_tro, ['nhân viên', 'quản lý', 'chủ cửa hàng'], true);
    }

    /**
     * Tạo ca mới: chỉ manager hoặc chủ cửa hàng.
     */
    public function create(NguoiDung $user): bool
    {
        return in_array($user->vai_tro, ['quản lý', 'chủ cửa hàng'], true);
    }

    /**
     * Force checkout nhân viên: chỉ manager hoặc chủ cửa hàng.
     */
    public function forceCheckout(NguoiDung $user): bool
    {
        return in_array($user->vai_tro, ['quản lý', 'chủ cửa hàng'], true);
    }

    /**
     * Redirect route sau khi check-in (dựa trên vai trò).
     */
    public static function resolveCheckinRedirectRoute(NguoiDung $user): string
    {
        return $user->vai_tro === 'nhân viên'
            ? 'staff.dashboard'
            : 'manager.shifts.index';
    }
}
