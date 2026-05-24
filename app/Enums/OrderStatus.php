<?php

namespace App\Enums;

enum OrderStatus: string
{
    case CHO_XAC_NHAN = 'chờ xác nhận';
    case DA_XAC_NHAN = 'đã xác nhận';
    case DA_HUY = 'đã hủy';

    /**
     * Chuẩn hóa chuỗi trạng thái đơn hàng từ nhiều dạng input.
     */
    public static function normalize(?string $status): ?self
    {
        if ($status === null || $status === '') {
            return null;
        }

        return match ($status) {
            'cho_xac_nhan', 'chờ xác nhận' => self::CHO_XAC_NHAN,
            'đã xác nhận', 'dang_pha_che', 'đang pha chế',
            'hoan_thanh', 'hoàn thành', 'da_giao', 'đã giao',
            'da_nhan', 'đã nhận' => self::DA_XAC_NHAN,
            'huy', 'đã hủy' => self::DA_HUY,
            default => null,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
