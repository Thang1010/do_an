<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChotCa extends Model
{

    protected $table = 'chot_ca';

    protected $fillable = [
        'ca_lam_viec_id',
        'nguoi_chot_id',
        'so_tien_dau_ca',
        'chot_luc',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'so_tien_dau_ca' => 'decimal:2',
            'chot_luc' => 'datetime',
        ];
    }

    public function caLamViec()
    {
        return $this->belongsTo(CaLamViec::class, 'ca_lam_viec_id');
    }

    public function nguoiChot()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_chot_id');
    }
}
