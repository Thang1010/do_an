<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanAn extends Model
{
    protected $table = 'ban_an';

    protected $fillable = [
        'so_ban',
        'trang_thai',
    ];

    public function donHang()
    {
        return $this->hasMany(DonHang::class, 'ban_an_id');
    }
}
