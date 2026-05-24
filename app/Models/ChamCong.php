<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChamCong extends Model
{

    protected $table = 'cham_cong';

    protected $fillable = [
        'nguoi_dung_id',
        'ca_lam_viec_id',
        'check_in_luc',
        'check_out_luc',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'check_in_luc' => 'datetime',
            'check_out_luc' => 'datetime',
        ];
    }

    public function nhanVien()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function caLamViec()
    {
        return $this->belongsTo(CaLamViec::class, 'ca_lam_viec_id');
    }
}