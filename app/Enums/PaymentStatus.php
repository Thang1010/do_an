<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case CHUA_THANH_TOAN = 'chưa thanh toán';
    case DA_THANH_TOAN = 'đã thanh toán';
    case THAT_BAI = 'thất bại';

    /**
     * Chuẩn hóa chuỗi trạng thái thanh toán từ nhiều dạng input.
     */
    public static function normalize(?string $status): ?self
    {
        if ($status === null || $status === '') {
            return null;
        }

        return match ($status) {
            'chua_thanh_toan', 'chưa thanh toán' => self::CHUA_THANH_TOAN,
            'da_thanh_toan', 'đã thanh toán' => self::DA_THANH_TOAN,
            'that_bai', 'thất bại', 'thanh toán thất bại' => self::THAT_BAI,
            default => null,
        };
    }

    /**
     * Chuyển thành trạng thái cho bản ghi ThanhToan.
     */
    public function toPaymentRecordStatus(): string
    {
        return match ($this) {
            self::DA_THANH_TOAN => 'đã thanh toán',
            self::THAT_BAI => 'thanh toán thất bại',
            default => 'chờ thanh toán',
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
