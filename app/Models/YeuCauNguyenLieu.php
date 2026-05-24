<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YeuCauNguyenLieu extends Model
{

    protected $table = 'yeu_cau_nguyen_lieu';

    protected $fillable = [
        'cua_hang_id',
        'nguoi_gui_id',
        'nguoi_duyet_id',
        'trang_thai',
        'du_lieu',
        'ghi_chu',
        'duyet_luc',
        'tu_choi_luc',
    ];

    protected function casts(): array
    {
        return [
            'du_lieu' => 'array',
            'duyet_luc' => 'datetime',
            'tu_choi_luc' => 'datetime',
        ];
    }

    public function nguoiGui()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_gui_id');
    }

    public function nguoiDuyet()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_duyet_id');
    }

    public function cuaHang()
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }
}
