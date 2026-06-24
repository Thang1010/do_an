<?php

namespace Tests\Feature\Manager\Concerns;

use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\CongThucSanPham;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\KichCo;
use App\Models\LichSuKho;
use App\Models\NguoiDung;
use App\Models\NguyenLieu;
use App\Models\SanPham;
use App\Models\ThanhToan;
use Illuminate\Support\Str;

/**
 * Helper tạo dữ liệu cho các test quản lý đơn hàng.
 *
 * Dự án chưa dùng database/factories nên các bản ghi được tạo thủ công
 * đúng theo cột bắt buộc trong migration & $fillable của từng model.
 */
trait InteractsWithOrders
{
    // ─── Người dùng theo vai trò ────────────────────────────────────────
    protected function createUser(string $vaiTro): NguoiDung
    {
        return NguoiDung::create([
            'email'      => Str::lower(Str::random(10)) . '@example.com',
            'mat_khau'   => 'password',          // cast 'hashed' tự băm
            'vai_tro'    => $vaiTro,
            'trang_thai' => 'hoạt động',
        ]);
    }

    protected function owner(): NguoiDung    { return $this->createUser('chủ cửa hàng'); }
    protected function manager(): NguoiDung  { return $this->createUser('quản lý'); }
    protected function staff(): NguoiDung    { return $this->createUser('nhân viên'); }
    protected function customer(): NguoiDung { return $this->createUser('khách hàng'); }

    // ─── Danh mục / Sản phẩm / Kích cỡ ──────────────────────────────────
    protected function createCategory(string $ten = 'Cà phê'): DanhMuc
    {
        return DanhMuc::create([
            'ten_danh_muc' => $ten,
            'slug'         => Str::slug($ten) . '-' . Str::random(5),
            'trang_thai'   => 'đang dùng',
        ]);
    }

    protected function createProduct(array $overrides = []): SanPham
    {
        $attrs = array_merge([
            'ten_san_pham'     => 'SP ' . Str::random(6),
            'slug'             => 'sp-' . Str::random(8),
            'gia_goc'          => 20000,
            'trang_thai_ban'   => 'đang bán',
            'loai_quan_ly_kho' => 'theo nguyên liệu',
        ], $overrides);

        // Giống ProductController: khi không có khuyến mãi thì gia_khuyen_mai = gia_goc
        // (cột NOT NULL default 0, controller dùng gia_khuyen_mai ?? gia_goc nên 0 sẽ thành giá 0).
        $attrs['gia_khuyen_mai'] ??= $attrs['gia_goc'];
        $attrs['danh_muc_id'] ??= $this->createCategory()->id;

        return SanPham::create($attrs);
    }

    /** Tạo 1 kích cỡ master và gắn vào sản phẩm qua bảng trung gian san_pham_kich_co. */
    protected function attachSize(SanPham $product, float $heSoGia = 1.0, string $ten = 'M'): KichCo
    {
        $size = KichCo::create([
            'ten_kich_co' => $ten,
            'ma_kich_co'  => Str::upper(Str::random(8)),
            'he_so_gia'   => $heSoGia,
        ]);

        $product->kichCo()->attach($size->id);

        return $size;
    }

    // ─── Bàn ăn ─────────────────────────────────────────────────────────
    protected function createTable(string $trangThai = 'trống'): BanAn
    {
        return BanAn::create([
            'so_ban'     => 'B' . Str::upper(Str::random(5)),
            'trang_thai' => $trangThai,
        ]);
    }

    // ─── Kho / Công thức ────────────────────────────────────────────────
    protected function createIngredient(string $ten = 'Nguyên liệu'): NguyenLieu
    {
        return NguyenLieu::create([
            'ten_nguyen_lieu' => $ten . ' ' . Str::random(4),
            'don_vi_tinh'     => 'ml',
            'trang_thai'      => NguyenLieu::TRANG_THAI_DANG_DUNG,
        ]);
    }

    protected function addRecipe(SanPham $product, NguyenLieu $ingredient, float $soLuongCan): CongThucSanPham
    {
        return CongThucSanPham::create([
            'san_pham_id'   => $product->id,
            'nguyen_lieu_id' => $ingredient->id,
            'so_luong_can'  => $soLuongCan,
        ]);
    }

    protected function addStock(NguyenLieu $ingredient, float $qty): LichSuKho
    {
        return LichSuKho::create([
            'nguyen_lieu_id' => $ingredient->id,
            'loai_giao_dich' => 'nhập kho',
            'so_luong'       => $qty,
            'ghi_chu'        => 'Nhập kho test',
        ]);
    }

    // ─── Đơn hàng có sẵn (dùng cho test sửa/xóa) ────────────────────────
    /**
     * @param array{
     *   nguoi_dung_id?:int|null, nhan_vien_id?:int|null, ban_an_id?:int|null,
     *   loai_don?:string, trang_thai_thanh_toan?:string, ma_don_hang?:string,
     *   items: array<int, array<string, mixed>>
     * } $opts
     */
    protected function makeOrder(array $opts): DonHang
    {
        $order = DonHang::create([
            'ma_don_hang'  => $opts['ma_don_hang'] ?? ('DH' . now()->format('ymdHis') . Str::upper(Str::random(5))),
            'nguoi_dung_id' => $opts['nguoi_dung_id'] ?? null,
            'nhan_vien_id' => $opts['nhan_vien_id'] ?? null,
            'ban_an_id'    => $opts['ban_an_id'] ?? null,
        ]);

        foreach ($opts['items'] as $item) {
            $donGia  = $item['don_gia'] ?? 20000;
            $soLuong = $item['so_luong'] ?? 1;

            ChiTietDonHang::create(array_merge([
                'don_hang_id'           => $order->id,
                'ten_san_pham'          => $item['ten_san_pham'] ?? 'SP',
                'ten_kich_co'           => $item['ten_kich_co'] ?? null,
                'don_gia'               => $donGia,
                'so_luong'              => $soLuong,
                'thanh_tien'            => $donGia * $soLuong,
                'tong_tien'             => $donGia * $soLuong,
                'loai_don'              => $opts['loai_don'] ?? 'sử dụng ngay',
                'trang_thai_thanh_toan' => $opts['trang_thai_thanh_toan'] ?? 'chưa thanh toán',
                'created_at'            => now(),
            ], $item));
        }

        return $order->fresh('chiTietDonHang');
    }

    protected function makePayment(DonHang $order, string $trangThai = 'chưa thanh toán'): ThanhToan
    {
        return ThanhToan::create([
            'don_hang_id' => $order->id,
            'phuong_thuc' => 'tiền mặt',
            'so_tien'     => $order->tong_tien,
            'trang_thai'  => $trangThai,
        ]);
    }
}
