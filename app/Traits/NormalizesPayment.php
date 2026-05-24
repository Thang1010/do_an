<?php

namespace App\Traits;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

/**
 * Trait cung cấp các phương thức chuẩn hóa thanh toán.
 *
 * Dùng trong các Controller xử lý thanh toán đơn hàng
 * để tránh trùng lặp code normalizePaymentMethod(), normalizePaymentStatus().
 */
trait NormalizesPayment
{
    protected function normalizePaymentMethod(?string $method): ?string
    {
        return PaymentMethod::normalize($method)?->value;
    }

    protected function normalizePaymentStatus(?string $status): ?string
    {
        return PaymentStatus::normalize($status)?->value;
    }

    protected function toPaymentRecordStatus(string $orderPaymentStatus): string
    {
        $enum = PaymentStatus::normalize($orderPaymentStatus);

        return $enum?->toPaymentRecordStatus() ?? 'chờ thanh toán';
    }
}
