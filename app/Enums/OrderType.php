<?php

namespace App\Enums;

enum OrderType: string
{
    case DAT_ONLINE = 'đặt online';
    case MUA_TAI_QUAN = 'mua tại quán';
    case GOI_TAI_BAN_QR = 'gọi tại bàn bằng qr';

    /**
     * Các loại đơn yêu cầu phải có bàn ăn.
     */
    public function requiresTable(): bool
    {
        return match ($this) {
            self::MUA_TAI_QUAN, self::GOI_TAI_BAN_QR => true,
            self::DAT_ONLINE => false,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
