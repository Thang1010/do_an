<?php

namespace App\Enums;

enum UserStatus: string
{
    case HOAT_DONG = 'hoạt động';
    case BI_KHOA = 'bị khóa';
    case NGUNG_HOAT_DONG = 'ngưng hoạt động';

    public function label(): string
    {
        return $this->value;
    }
}
