<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Cửa hàng mặc định (thường đã có từ migration, lấy ID của nó)
        $cuaHang = DB::table('cua_hang')->first();
        $cuaHangId = $cuaHang ? $cuaHang->id : 1;

        // 2. Chức Vụ
        $cvPhaCheId = DB::table('chuc_vu')->insertGetId([
            'ten_chuc_vu' => 'Pha chế',
            'vai_tro_ap_dung' => 'nhân viên',
            'luong_co_ban' => 5000000,
            'loai_hinh_lam_viec' => 'toàn thời gian',
            'luong_theo_gio' => 20000,
        ]);

        $cvPhucVuId = DB::table('chuc_vu')->insertGetId([
            'ten_chuc_vu' => 'Phục vụ',
            'vai_tro_ap_dung' => 'nhân viên',
            'luong_co_ban' => 0,
            'loai_hinh_lam_viec' => 'bán thời gian',
            'luong_theo_gio' => 25000,
        ]);

        // 3. Người Dùng
        // Admin
        $adminId = DB::table('nguoi_dung')->insertGetId([
            'cua_hang_id' => $cuaHangId,
            'email' => 'admin@gmail.com',
            'mat_khau' => Hash::make('12345678'),
            'vai_tro' => 'chủ cửa hàng',
            'trang_thai' => 'hoạt động',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Manager
        $managerId = DB::table('nguoi_dung')->insertGetId([
            'cua_hang_id' => $cuaHangId,
            'email' => 'manager@gmail.com',
            'mat_khau' => Hash::make('12345678'),
            'vai_tro' => 'quản lý',
            'trang_thai' => 'hoạt động',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('ho_so_quan_ly')->insert([
            'nguoi_dung_id' => $managerId,
            'cua_hang_id' => $cuaHangId,
            'ho_ten' => 'Nguyễn Quản Lý',
            'so_dien_thoai' => '0901234567',
        ]);

        // Staff 1
        $staff1Id = DB::table('nguoi_dung')->insertGetId([
            'cua_hang_id' => $cuaHangId,
            'email' => 'staff1@gmail.com',
            'mat_khau' => Hash::make('12345678'),
            'vai_tro' => 'nhân viên',
            'trang_thai' => 'hoạt động',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('ho_so_nhan_vien')->insert([
            'nguoi_dung_id' => $staff1Id,
            'chuc_vu_id' => $cvPhaCheId,
            'ho_ten' => 'Trần Pha Chế',
            'so_dien_thoai' => '0911234567',
            'ngay_vao_lam' => $now->toDateString(),
        ]);

        // Staff 2
        $staff2Id = DB::table('nguoi_dung')->insertGetId([
            'cua_hang_id' => $cuaHangId,
            'email' => 'staff2@gmail.com',
            'mat_khau' => Hash::make('12345678'),
            'vai_tro' => 'nhân viên',
            'trang_thai' => 'hoạt động',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('ho_so_nhan_vien')->insert([
            'nguoi_dung_id' => $staff2Id,
            'chuc_vu_id' => $cvPhucVuId,
            'ho_ten' => 'Lê Phục Vụ',
            'so_dien_thoai' => '0921234567',
            'ngay_vao_lam' => $now->toDateString(),
        ]);

        // Customer
        $customerId = DB::table('nguoi_dung')->insertGetId([
            'cua_hang_id' => $cuaHangId,
            'email' => 'khachhang@gmail.com',
            'mat_khau' => Hash::make('12345678'),
            'vai_tro' => 'khách hàng',
            'trang_thai' => 'hoạt động',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('ho_so_khach_hang')->insert([
            'nguoi_dung_id' => $customerId,
            'ho_ten' => 'Khách Hàng Vip',
            'dia_chi' => '123 Đường Số 1, TPHCM'
        ]);

        // 4. Danh Mục
        $dmCafeId = DB::table('danh_muc')->insertGetId(['ten_danh_muc' => 'Cà phê', 'slug' => 'ca-phe', 'created_at' => $now]);
        $dmTraId = DB::table('danh_muc')->insertGetId(['ten_danh_muc' => 'Trà trái cây', 'slug' => 'tra-trai-cay', 'created_at' => $now]);

        // 5. Kích Cỡ
        $kcMId = DB::table('kich_co')->insertGetId(['ten_kich_co' => 'Size M', 'ma_kich_co' => 'M', 'he_so_gia' => 0, 'created_at' => $now]);
        $kcLId = DB::table('kich_co')->insertGetId(['ten_kich_co' => 'Size L', 'ma_kich_co' => 'L', 'he_so_gia' => 5000, 'created_at' => $now]);

        $nlCafeId = DB::table('nguyen_lieu')->insertGetId(['ten_nguyen_lieu' => 'Hạt cà phê Robusta', 'don_vi_tinh' => 'gram']);
        $nlSuaId = DB::table('nguyen_lieu')->insertGetId(['ten_nguyen_lieu' => 'Sữa đặc Ngôi Sao', 'don_vi_tinh' => 'hộp']);
        $nlTraId = DB::table('nguyen_lieu')->insertGetId(['ten_nguyen_lieu' => 'Trà đen', 'don_vi_tinh' => 'gói']);
        $nlDaoId = DB::table('nguyen_lieu')->insertGetId(['ten_nguyen_lieu' => 'Đào ngâm', 'don_vi_tinh' => 'hộp']);

        DB::table('lich_su_kho')->insert([
            ['nguyen_lieu_id' => $nlCafeId, 'loai_giao_dich' => 'nhập kho', 'so_luong' => 10, 'gia_nhap' => 150000, 'nguoi_tao_id' => $managerId, 'created_at' => $now],
            ['nguyen_lieu_id' => $nlSuaId, 'loai_giao_dich' => 'nhập kho', 'so_luong' => 20, 'gia_nhap' => 20000, 'nguoi_tao_id' => $managerId, 'created_at' => $now],
            ['nguyen_lieu_id' => $nlTraId, 'loai_giao_dich' => 'nhập kho', 'so_luong' => 5, 'gia_nhap' => 120000, 'nguoi_tao_id' => $managerId, 'created_at' => $now],
            ['nguyen_lieu_id' => $nlDaoId, 'loai_giao_dich' => 'nhập kho', 'so_luong' => 15, 'gia_nhap' => 45000, 'nguoi_tao_id' => $managerId, 'created_at' => $now],
        ]);

        // 7. Sản Phẩm
        $sp1Id = DB::table('san_pham')->insertGetId([
            'danh_muc_id' => $dmCafeId, 'ten_san_pham' => 'Cà phê sữa đá', 'slug' => 'ca-phe-sua-da',
            'gia_goc' => 25000, 'nhiet_do' => 'lạnh', 'loai_quan_ly_kho' => 'theo nguyên liệu', 'created_at' => $now
        ]);
        $sp2Id = DB::table('san_pham')->insertGetId([
            'danh_muc_id' => $dmCafeId, 'ten_san_pham' => 'Cà phê đen đá', 'slug' => 'ca-phe-den-da',
            'gia_goc' => 20000, 'nhiet_do' => 'lạnh', 'loai_quan_ly_kho' => 'theo nguyên liệu', 'created_at' => $now
        ]);
        $sp3Id = DB::table('san_pham')->insertGetId([
            'danh_muc_id' => $dmTraId, 'ten_san_pham' => 'Trà đào cam sả', 'slug' => 'tra-dao-cam-sa',
            'gia_goc' => 35000, 'nhiet_do' => 'lạnh', 'loai_quan_ly_kho' => 'theo nguyên liệu', 'created_at' => $now
        ]);

        // Gắn Kích Cỡ cho Sản Phẩm (Pivot table)
        DB::table('san_pham_kich_co')->insert([
            ['san_pham_id' => $sp1Id, 'kich_co_id' => $kcMId, 'created_at' => $now, 'updated_at' => $now],
            ['san_pham_id' => $sp1Id, 'kich_co_id' => $kcLId, 'created_at' => $now, 'updated_at' => $now],
            ['san_pham_id' => $sp2Id, 'kich_co_id' => $kcMId, 'created_at' => $now, 'updated_at' => $now],
            ['san_pham_id' => $sp3Id, 'kich_co_id' => $kcMId, 'created_at' => $now, 'updated_at' => $now],
            ['san_pham_id' => $sp3Id, 'kich_co_id' => $kcLId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Công Thức Sản Phẩm
        DB::table('cong_thuc_san_pham')->insert([
            ['san_pham_id' => $sp1Id, 'nguyen_lieu_id' => $nlCafeId, 'so_luong_can' => 0.02, 'created_at' => $now],
            ['san_pham_id' => $sp1Id, 'nguyen_lieu_id' => $nlSuaId, 'so_luong_can' => 0.05, 'created_at' => $now],
            ['san_pham_id' => $sp3Id, 'nguyen_lieu_id' => $nlTraId, 'so_luong_can' => 0.01, 'created_at' => $now],
            ['san_pham_id' => $sp3Id, 'nguyen_lieu_id' => $nlDaoId, 'so_luong_can' => 0.1, 'created_at' => $now],
        ]);

        // 8. Bàn Ăn
        $banAnData = [];
        for ($i = 1; $i <= 10; $i++) {
            $banAnData[] = [
                'so_ban' => "Bàn $i",
                'trang_thai' => 'trống'
            ];
        }
        DB::table('ban_an')->insert($banAnData);

        // 9. Voucher
        DB::table('voucher')->insert([
            'ma_voucher' => 'KHAITRUONG',
            'ten_voucher' => 'Mừng Khai Trương',
            'loai_giam' => 'phần trăm',
            'gia_tri_giam' => 20,
            'don_toi_thieu' => 50000,
            'giam_toi_da' => 30000,
            'so_luong' => 100,
            'trang_thai' => 'đang hoạt động',
            'ngay_bat_dau' => clone $now,
            'ngay_ket_thuc' => (clone $now)->addMonths(1),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
