<?php

namespace App\Enums;

enum ProductStatus: string
{
    case DANG_BAN = 'đang bán';
    case NGUNG_BAN = 'ngừng bán';

    public function label(): string
    {
        return $this->value;
    }
}
