<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhienChat extends Model
{
    protected $table = 'phien_chat';

    protected $fillable = [
        'nguoi_dung_id',
        'kenh_chat',
        'trang_thai',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function tinNhanChat()
    {
        return $this->hasMany(TinNhanChat::class, 'phien_chat_id');
    }
}
