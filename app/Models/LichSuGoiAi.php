<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LichSuGoiAi extends Model
{
    use HasFactory;

    protected $table = 'lich_su_goi_ai';

    public $timestamps = false;

    protected $fillable = [
        'phien_chat_id',
        'ten_mo_hinh',
        'noi_dung_gui_di',
        'noi_dung_nhan_ve',
        'token_dau_vao',
        'token_dau_ra',
        'tong_token',
        'chi_phi_uoc_tinh',
        'trang_thai',
        'thong_bao_loi',
        'created_at',
    ];

    public function phienChat()
    {
        return $this->belongsTo(PhienChat::class, 'phien_chat_id');
    }
}