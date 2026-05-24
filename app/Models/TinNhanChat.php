<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinNhanChat extends Model
{

    protected $table = 'tin_nhan_chat';

    public $timestamps = false;

    protected $fillable = [
        'phien_chat_id',
        'nguoi_gui',
        'noi_dung',
        'loai_tin_nhan',
        'y_dinh',
        'so_token',
        'thoi_gian_phan_hoi_ms',
        'created_at',
    ];

    public function phienChat()
    {
        return $this->belongsTo(PhienChat::class, 'phien_chat_id');
    }
}