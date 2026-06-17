<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SanPham extends Model
{

    protected $table = 'san_pham';

    protected $fillable = [
        'danh_muc_id',
        'ten_san_pham',
        'slug',
        'mo_ta',
        'mo_ta_chi_tiet',
        'gia_goc',
        'gia_khuyen_mai',
        'hinh_anh',
        'trang_thai_ban',
        'nhiet_do',
        'loai_quan_ly_kho',
        'noi_bat',
    ];

    protected function casts(): array
    {
        return [
            'gia_goc' => 'float',
            'gia_khuyen_mai' => 'float',
        ];
    }

    public function danhMuc()
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'san_pham_id');
    }

    public function danhGiaSanPham()
    {
        return $this->hasMany(DanhGiaSanPham::class, 'san_pham_id');
    }

    /**
     * Product-specific sizes (kich_co belongs to this product via san_pham_id FK).
     */
    public function kichCo()
    {
        return $this->belongsToMany(KichCo::class, 'san_pham_kich_co', 'san_pham_id', 'kich_co_id')
                    ->withTimestamps()
                    ->orderBy('he_so_gia');
    }

    /**
     * Alias for backward compatibility with views/controllers using sanPhamKichCo.
     * Returns the product's KichCo collection.
     */
    public function sanPhamKichCo()
    {
        return $this->hasMany(KichCo::class, 'san_pham_id');
    }

    public function congThucSanPham()
    {
        return $this->hasMany(CongThucSanPham::class, 'san_pham_id');
    }

    public function getGiaAttribute()
    {
        return $this->gia_khuyen_mai > 0 ? $this->gia_khuyen_mai : $this->gia_goc;
    }

    public function getTrangThaiAttribute()
    {
        return $this->trang_thai_ban === 'đang bán' ? 'dang_ban' : 'ngung_ban';
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->hinh_anh) {
            if (Str::startsWith($this->hinh_anh, ['http://', 'https://'])) {
                return $this->hinh_anh;
            }

            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->hinh_anh)) {
                return asset('storage/' . $this->hinh_anh);
            }

            try {
                return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->hinh_anh);
            } catch (\Exception $e) {
                return asset('storage/' . $this->hinh_anh);
            }
        }

        // Nếu sản phẩm không có ảnh, trả về ảnh chứa chữ cái đầu tiên của tên sản phẩm
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->ten_san_pham) . '&background=E2D9C8&color=30261C&size=500';
    }

    public function nguoiDungYeuThich()
    {
        return $this->belongsToMany(NguoiDung::class, 'san_pham_yeu_thich', 'san_pham_id', 'nguoi_dung_id')
                    ->withTimestamps();
    }
}
