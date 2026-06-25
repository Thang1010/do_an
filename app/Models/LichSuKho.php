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
        'don_hang_id',
        'so_luong',
        'gia_nhap',
        'ghi_chu',
        'nguoi_tao_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'don_hang_id' => 'integer',
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
                // 1) Sản phẩm CÓ công thức dùng nguyên liệu này.
                $productIds = CongThucSanPham::where('nguyen_lieu_id', $ingredientId)->pluck('san_pham_id');

                // 2) Sản phẩm BÁN LẺ (không công thức): nguyên liệu này CHÍNH LÀ sản phẩm đó
                //    (gắn qua nguyen_lieu.san_pham_id) → hết hàng cũng phải ngừng bán.
                $selfProductId = NguyenLieu::whereKey($ingredientId)->value('san_pham_id');
                if ($selfProductId) {
                    $productIds = $productIds->push($selfProductId);
                }

                if ($productIds->isNotEmpty()) {
                    // Áp cho cả 'theo nguyên liệu' lẫn 'theo số lượng' — id đã được lấy đúng
                    // theo từng loại ở trên nên không cần lọc loai_quan_ly_kho nữa.
                    SanPham::whereIn('id', $productIds->unique()->values())
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

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function chiTieu()
    {
        return $this->hasOne(ChiTieu::class, 'lich_su_kho_id');
    }
}