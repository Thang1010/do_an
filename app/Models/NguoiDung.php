<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class NguoiDung extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'nguoi_dung';

    protected $fillable = [
        'ho_ten',
        'email',
        'so_dien_thoai',
        'mat_khau',
        'vai_tro',
        'trang_thai',
        'anh_dai_dien',
        'email_da_xac_thuc_luc',
    ];

    protected $hidden = [
        'mat_khau',
        'remember_token',
    ];

    /**
     * Password field cho Authenticatable.
     */
    public function getAuthPassword(): string
    {
        return $this->mat_khau;
    }

    /**
     * Casting.
     */
    protected function casts(): array
    {
        return [
            'email_da_xac_thuc_luc' => 'datetime',
            'mat_khau'              => 'hashed',
        ];
    }

    // =====================
    // ROLE HELPERS
    // =====================

    public function isKhachHang(): bool { return $this->vai_tro === 'khách hàng'; }
    public function isNhanVien(): bool  { return $this->vai_tro === 'nhân viên'; }
    public function isQuanLy(): bool    { return $this->vai_tro === 'quản lý'; }
    public function isActive(): bool    { return $this->trang_thai === 'hoạt động'; }

    /**
     * URL ảnh đại diện.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->anh_dai_dien) {
            return asset('storage/' . $this->anh_dai_dien);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->ho_ten)
            . '&background=E2D9C8&color=30261C&bold=true';
    }

    public function hoSoKhachHang()
    {
        return $this->hasOne(HoSoKhachHang::class, 'nguoi_dung_id');
    }

    public function hoSoNhanVien()
    {
        return $this->hasOne(HoSoNhanVien::class, 'nguoi_dung_id');
    }

    public function hoSoQuanLy()
    {
        return $this->hasOne(HoSoQuanLy::class, 'nguoi_dung_id');
    }

    public function donHang()
    {
        return $this->hasMany(DonHang::class, 'nguoi_dung_id');
    }

    public function donHangXuLy()
    {
        return $this->hasMany(DonHang::class, 'nhan_vien_id');
    }

    public function voucherNguoiDung()
    {
        return $this->hasMany(VoucherNguoiDung::class, 'nguoi_dung_id');
    }

    public function lichSuDiemThuong()
    {
        return $this->hasMany(LichSuDiemThuong::class, 'nguoi_dung_id');
    }

    public function danhGiaSanPham()
    {
        return $this->hasMany(DanhGiaSanPham::class, 'nguoi_dung_id');
    }

    public function phienChat()
    {
        return $this->hasMany(PhienChat::class, 'nguoi_dung_id');
    }
}