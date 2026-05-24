<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhienChat extends Model
{

    protected $table = 'phien_chat';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_phien',
        'kenh_chat',
        'ban_an_id',
        'tieu_de',
        'bat_dau_luc',
        'ket_thuc_luc',
        'trang_thai',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function banAn()
    {
        return $this->belongsTo(BanAn::class, 'ban_an_id');
    }

    public function tinNhanChat()
    {
        return $this->hasMany(TinNhanChat::class, 'phien_chat_id');
    }

    public function lichSuGoiAi()
    {
        return $this->hasMany(LichSuGoiAi::class, 'phien_chat_id');
    }
}