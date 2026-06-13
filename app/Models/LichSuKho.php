<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuKho extends Model
{

    protected $table = 'lich_su_kho';

    public $timestamps = false;

    protected $fillable = [
        'nguyen_lieu_id',
        'loai_giao_dich',
        'tham_chieu_loai',
        'tham_chieu_id',
        'so_luong',
        'gia_nhap',
        'ghi_chu',
        'nguoi_tao_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tham_chieu_id' => 'integer',
            'so_luong' => 'decimal:2',
            'gia_nhap' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $checkStock = function (LichSuKho $lichSuKho) {
            $ingredientId = $lichSuKho->nguyen_lieu_id;
            
            $balanceExpression = \App\Enums\TransactionType::stockBalanceExpression('lich_su_kho');
            $currentStock = (float) (self::query()
                ->where('nguyen_lieu_id', $ingredientId)
                ->selectRaw("COALESCE({$balanceExpression}, 0) as so_luong")
                ->value('so_luong') ?? 0);

            if ($currentStock <= 0) {
                $productIds = CongThucSanPham::where('nguyen_lieu_id', $ingredientId)->pluck('san_pham_id');
                if ($productIds->isNotEmpty()) {
                    SanPham::whereIn('id', $productIds)
                        ->where('loai_quan_ly_kho', 'theo nguyên liệu')
                        ->where('trang_thai_ban', 'đang bán')
                        ->update(['trang_thai_ban' => 'ngừng bán']);
                }
            }
        };

        static::saved($checkStock);
        static::deleted($checkStock);
    }

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }
}