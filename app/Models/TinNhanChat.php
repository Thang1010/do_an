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
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function phienChat()
    {
        return $this->belongsTo(PhienChat::class, 'phien_chat_id');
    }
}
