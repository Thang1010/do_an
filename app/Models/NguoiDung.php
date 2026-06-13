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
        'email',
        'google_id',
        'mat_khau',
        'vai_tro',
        'trang_thai',
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

    public function notifications()
    {
        return $this->morphMany(ThongBao::class, 'doi_tuong', 'doi_tuong_loai', 'doi_tuong_id')->latest();
    }

    public function getHoTenAttribute(): ?string
    {
        $hoTen = null;
        if ($this->isKhachHang()) {
            $hoTen = $this->hoSoKhachHang?->ho_ten;
        } elseif ($this->isNhanVien()) {
            $hoTen = $this->hoSoNhanVien?->ho_ten;
        } elseif ($this->isQuanLy() || $this->isChuCuaHang()) {
            $hoTen = $this->hoSoQuanLy?->ho_ten;
        }
        
        return $hoTen ?: $this->email;
    }

    public function getSoDienThoaiAttribute(): ?string
    {
        if ($this->isKhachHang()) {
            return $this->hoSoKhachHang?->so_dien_thoai;
        } elseif ($this->isNhanVien()) {
            return $this->hoSoNhanVien?->so_dien_thoai;
        } elseif ($this->isQuanLy() || $this->isChuCuaHang()) {
            return $this->hoSoQuanLy?->so_dien_thoai;
        }
        return null;
    }

    /**
     * URL ảnh đại diện - lấy từ hồ sơ tương ứng.
     */
    public function getAvatarUrlAttribute(): string
    {
        $avatar = null;
        if ($this->isKhachHang()) {
            $avatar = $this->hoSoKhachHang?->anh_dai_dien;
        } elseif ($this->isNhanVien()) {
            $avatar = $this->hoSoNhanVien?->anh_dai_dien;
        } elseif ($this->isQuanLy() || $this->isChuCuaHang()) {
            $avatar = $this->hoSoQuanLy?->anh_dai_dien;
        }

        if ($avatar) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($avatar)) {
                return asset('storage/' . $avatar);
            }
            try {
                return \Illuminate\Support\Facades\Storage::disk('s3')->url($avatar);
            } catch (\Exception $e) {
                return asset('storage/' . $avatar);
            }
        }
        $displayName = $this->ho_ten ?? $this->email;
        return 'https://ui-avatars.com/api/?name=' . urlencode($displayName)
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

    /**
     * Override notification table name for thong_bao.
     */
    public function routeNotificationForDatabase()
    {
        return $this->notifications();
    }
}