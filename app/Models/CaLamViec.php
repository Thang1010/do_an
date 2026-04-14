<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaLamViec extends Model
{
    use HasFactory;

    protected $table = 'ca_lam_viec';

    protected $fillable = [
        'ten_ca',
        'gio_bat_dau',
        'gio_ket_thuc',
    ];

    public function phanCongCa()
    {
        return $this->hasMany(PhanCongCa::class, 'ca_lam_viec_id');
    }
}