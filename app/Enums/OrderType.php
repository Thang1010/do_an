<?php

namespace App\Enums;

enum OrderType: string
{
    case DAT_HANG_TRUOC = 'đặt hàng trước';
    case SU_DUNG_NGAY = 'sử dụng ngay';
    case GOI_TAI_BAN_QR = 'gọi tại bàn bằng qr';
    case MANG_VE = 'mang về';

    /**
     * Các loại đơn yêu cầu phải có bàn ăn.
     */
    public function requiresTable(): bool
    {
        return match ($this) {
            self::SU_DUNG_NGAY, self::GOI_TAI_BAN_QR => true,
            self::DAT_HANG_TRUOC, self::MANG_VE => false,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
