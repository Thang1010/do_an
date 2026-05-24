<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaLamViec extends Model
{

    protected $table = 'ca_lam_viec';

    protected $fillable = [
        'nguoi_dung_id',
        'ten_ca',
        'ngay_lam',
        'gio_bat_dau',
        'gio_ket_thuc',
    ];

    protected function casts(): array
    {
        return [
            'ngay_lam' => 'date',
        ];
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chamCong()
    {
        return $this->hasMany(ChamCong::class, 'ca_lam_viec_id');
    }

    public function chiTieu()
    {
        return $this->hasMany(ChiTieu::class, 'ca_lam_viec_id');
    }

    public function chotCa()
    {
        return $this->hasOne(ChotCa::class, 'ca_lam_viec_id');
    }
}