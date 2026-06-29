<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanPhamKichCo extends Model
{
    protected $table = 'san_pham_kich_co';

    protected $fillable = [
        'san_pham_id',
        'kich_co_id',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function kichCo()
    {
        return $this->belongsTo(KichCo::class, 'kich_co_id');
    }
}
