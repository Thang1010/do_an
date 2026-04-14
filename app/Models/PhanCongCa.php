<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhanCongCa extends Model
{
    use HasFactory;

    protected $table = 'phan_cong_ca';

    public $timestamps = false;

    protected $fillable = [
        'nhan_vien_id',
        'ca_lam_viec_id',
        'ngay_lam',
        'trang_thai',
        'created_at',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NguoiDung::class, 'nhan_vien_id');
    }

    public function caLamViec()
    {
        return $this->belongsTo(CaLamViec::class, 'ca_lam_viec_id');
    }

    public function chamCong()
    {
        return $this->hasMany(ChamCong::class, 'phan_cong_ca_id');
    }
}