<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChucVu extends Model
{

    protected $table = 'chuc_vu';

    public $timestamps = false;

    protected $fillable = [
        'ten_chuc_vu',
        'vai_tro_ap_dung',
        'mo_ta_chuc_vu',
    ];

    protected function casts(): array
    {
        return [
            'vai_tro_ap_dung' => 'string',
        ];
    }

    public function hoSoNhanVien()
    {
        return $this->hasMany(HoSoNhanVien::class, 'chuc_vu_id');
    }

    public function hoSoQuanLy()
    {
        return $this->hasMany(HoSoQuanLy::class, 'chuc_vu_id');
    }
}
