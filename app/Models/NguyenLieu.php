<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NguyenLieu extends Model
{
    public const TRANG_THAI_DANG_DUNG = 'đang sử dụng';
    public const TRANG_THAI_NGUNG_DUNG = 'ngừng sử dụng';

    protected $table = 'nguyen_lieu';

    public $timestamps = false;

    protected $fillable = [
        'san_pham_id',
        'ten_nguyen_lieu',
        'don_vi_tinh',
        'muc_dich_su_dung',
        'trang_thai',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'san_pham_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** Chỉ nguyên liệu đang sử dụng (ẩn các nguyên liệu đã ngừng/lưu trữ). */
    public function scopeDangSuDung(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('nguyen_lieu.trang_thai', self::TRANG_THAI_DANG_DUNG)
                ->orWhereNull('nguyen_lieu.trang_thai');
        });
    }

    public function isDangSuDung(): bool
    {
        return ($this->trang_thai ?? self::TRANG_THAI_DANG_DUNG) === self::TRANG_THAI_DANG_DUNG;
    }

    public function lichSuKho()
    {
        return $this->hasMany(LichSuKho::class, 'nguyen_lieu_id');
    }

    public function congThucSanPham()
    {
        return $this->hasMany(CongThucSanPham::class, 'nguyen_lieu_id');
    }

    /** Sản phẩm không công thức mà nguyên liệu này đại diện trong kho. */
    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }
}
