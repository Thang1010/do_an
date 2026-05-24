<?php

namespace App\Traits;

use App\Models\DonHang;

/**
 * Trait tạo mã đơn hàng duy nhất.
 *
 * Dùng trong các Controller tạo đơn hàng (Manager\OrderController, Staff\TableController)
 * để tránh trùng lặp code generateOrderCode().
 */
trait GeneratesOrderCode
{
    protected function generateOrderCode(): string
    {
        do {
            $code = 'DH' . now()->format('ymdHis') . random_int(10, 99);
        } while (DonHang::query()->where('ma_don_hang', $code)->exists());

        return $code;
    }
}
