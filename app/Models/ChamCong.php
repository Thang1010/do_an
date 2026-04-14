<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChamCong extends Model
{
    use HasFactory;

    protected $table = 'cham_cong';

    protected $fillable = [
        'nhan_vien_id',
        'phan_cong_ca_id',
        'check_in_luc',
        'check_out_luc',
        'ghi_chu',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NguoiDung::class, 'nhan_vien_id');
    }

    public function phanCongCa()
    {
        return $this->belongsTo(PhanCongCa::class, 'phan_cong_ca_id');
    }
}