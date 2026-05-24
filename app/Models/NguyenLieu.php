<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguyenLieu extends Model
{

    protected $table = 'nguyen_lieu';

    public $timestamps = false;

    protected $fillable = [
        'ten_nguyen_lieu',
        'don_vi_tinh',
        'muc_dich_su_dung',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function lichSuKho()
    {
        return $this->hasMany(LichSuKho::class, 'nguyen_lieu_id');
    }

    public function congThucSanPham()
    {
        return $this->hasMany(CongThucSanPham::class, 'nguyen_lieu_id');
    }
}
