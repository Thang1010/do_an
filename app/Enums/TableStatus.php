<?php

namespace App\Enums;

enum TableStatus: string
{
    case TRONG = 'trống';
    case DANG_PHUC_VU = 'đang phục vụ';
    case DANG_CHO_DUYET = 'đang chờ duyệt';
    case DA_DAT = 'đã đặt';
    case NGUNG_SU_DUNG = 'ngưng sử dụng';

    /**
     * Chuẩn hóa chuỗi trạng thái bàn từ nhiều dạng input.
     */
    public static function normalize(?string $status): self
    {
        return match ($status) {
            'dang_phuc_vu', 'đang phục vụ' => self::DANG_PHUC_VU,
            'dang_cho_duyet', 'cho_duyet', 'đang chờ duyệt', 'chờ duyệt' => self::DANG_CHO_DUYET,
            'da_dat', 'đã đặt' => self::DA_DAT,
            'ngung_su_dung', 'ngưng sử dụng' => self::NGUNG_SU_DUNG,
            default => self::TRONG,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
