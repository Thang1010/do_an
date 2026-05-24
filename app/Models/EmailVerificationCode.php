<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationCode extends Model
{
    protected $table = 'xac_minh_email';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_xac_minh_ma_hoa',
        'het_han_luc',
        'xac_minh_luc',
    ];

    protected function casts(): array
    {
        return [
            'het_han_luc' => 'datetime',
            'xac_minh_luc' => 'datetime',
        ];
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}
