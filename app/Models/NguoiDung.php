<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class NguoiDung extends Authenticatable
{
    use Notifiable;

    protected $table = 'nguoi_dung';

    protected $fillable = [
        'cua_hang_id',
        'ho_ten',
        'email',
        'google_id',
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
        return $this->mat_khau ?? '';
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

    public function isKhachHang(): bool { return $this->vai_tro === UserRole::KHACH_HANG->value; }
    public function isNhanVien(): bool  { return $this->vai_tro === UserRole::NHAN_VIEN->value; }
    public function isQuanLy(): bool    { return $this->vai_tro === UserRole::QUAN_LY->value; }
    public function isChuCuaHang(): bool { return $this->vai_tro === UserRole::CHU_CUA_HANG->value; }
    public function isQuanTriCuaHang(): bool
    {
        return in_array($this->vai_tro, UserRole::managerRoleValues(), true);
    }
    public function isActive(): bool    { return $this->trang_thai === UserStatus::HOAT_DONG->value; }

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

    public function cuaHang()
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
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

    public function danhGiaSanPham()
    {
        return $this->hasMany(DanhGiaSanPham::class, 'nguoi_dung_id');
    }

    public function phienChat()
    {
        return $this->hasMany(PhienChat::class, 'nguoi_dung_id');
    }

    public function sanPhamYeuThich()
    {
        return $this->belongsToMany(SanPham::class, 'san_pham_yeu_thich', 'nguoi_dung_id', 'san_pham_id')
                    ->withTimestamps();
    }
}