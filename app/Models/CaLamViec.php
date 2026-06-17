<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaLamViec extends Model
{

    protected $table = 'ca_lam_viec';

    protected $fillable = [
        'nguoi_dung_id',
        'ten_ca',
        'ngay_lam',
        'gio_bat_dau',
        'gio_ket_thuc',
    ];

    protected function casts(): array
    {
        return [
            'ngay_lam' => 'date',
        ];
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chamCong()
    {
        return $this->hasMany(ChamCong::class, 'ca_lam_viec_id');
    }

    public function chiTieu()
    {
        return $this->hasMany(ChiTieu::class, 'ca_lam_viec_id');
    }

    public function chotCa()
    {
        return $this->hasOne(ChotCa::class, 'ca_lam_viec_id');
    }

    /**
     * Ca đã chốt hay chưa (chot_luc khác null). Kiểm tra theo cả nhóm ca
     * cùng (ngày, tên ca, giờ) vì bản ghi chốt có thể gắn ở ca khác trong nhóm.
     */
    public function daChot(): bool
    {
        $groupIds = static::query()
            ->where('ngay_lam', $this->ngay_lam)
            ->where('ten_ca', $this->ten_ca)
            ->where('gio_bat_dau', $this->gio_bat_dau)
            ->where('gio_ket_thuc', $this->gio_ket_thuc)
            ->pluck('id');

        return ChotCa::whereIn('ca_lam_viec_id', $groupIds)
            ->whereNotNull('chot_luc')
            ->exists();
    }
}