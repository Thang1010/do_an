<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingCustomerRegistration extends Model
{
    protected $table = 'tai_khoan_cho_xac_minh';

    protected $fillable = [
        'ho_ten',
        'email',
        'mat_khau_ma_hoa',
        'ma_xac_minh_ma_hoa',
        'het_han_luc',
    ];

    protected function casts(): array
    {
        return [
            'het_han_luc' => 'datetime',
        ];
    }
}
