<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DonHang;
use App\Models\NguoiDung;

/**
 * Policy kiểm soát quyền truy cập đơn hàng.
 *
 * Thay thế các inline abort(403) và canEditOrder() trong OrderController.
 */
class OrderPolicy
{
    /**
     * Chỉnh sửa đơn: phải là staff/manager và đơn ở trạng thái có thể sửa.
     */
    public function update(NguoiDung $user, DonHang $order): bool
    {
        return in_array($user->vai_tro, UserRole::staffRoleValues(), true)
            && $this->isEditable($order);
    }

    /**
     * Cập nhật thanh toán: phải là staff/manager.
     */
    public function updatePayment(NguoiDung $user, DonHang $order): bool
    {
        return in_array($user->vai_tro, UserRole::staffRoleValues(), true);
    }

    /**
     * Cập nhật trạng thái đơn: phải là staff/manager.
     */
    public function updateStatus(NguoiDung $user, DonHang $order): bool
    {
        return in_array($user->vai_tro, UserRole::staffRoleValues(), true);
    }

    /**
     * Đơn hàng có thể chỉnh sửa: chưa thanh toán.
     */
    public function isEditable(DonHang $order): bool
    {
        return $order->trang_thai_thanh_toan === 'chưa thanh toán';
    }
}
