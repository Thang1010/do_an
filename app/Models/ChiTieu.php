<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTieu extends Model
{

    protected $table = 'chi_tieu';

    protected $fillable = [
        'ca_lam_viec_id',
        'nguoi_tao_id',
        'nguyen_lieu_id',
        'lich_su_kho_id',
        'phuong_thuc_thanh_toan',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function caLamViec()
    {
        return $this->belongsTo(CaLamViec::class, 'ca_lam_viec_id');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }

    public function lichSuKho()
    {
        return $this->belongsTo(LichSuKho::class, 'lich_su_kho_id');
    }
}
