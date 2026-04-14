<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\NguoiDung;
use App\Models\SanPham;
use App\Models\NguyenLieu;
use App\Models\LichSuDiemThuong;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // ===== STAT CARDS =====
        $doanhThuHomNay = DonHang::whereDate('created_at', $today)
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->sum('tong_tien');

        $doanhThuHomQua = DonHang::whereDate('created_at', $today->copy()->subDay())
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->sum('tong_tien');

        $donHangHomNay = DonHang::whereDate('created_at', $today)->count();
        $donHangHomQua = DonHang::whereDate('created_at', $today->copy()->subDay())->count();

        $khachHangMoi = NguoiDung::whereDate('created_at', $today)
            ->where('vai_tro', 'khách hàng')
            ->count();

        // Nguyên liệu sắp hết
        $nguyenLieuSapHet = NguyenLieu::whereRaw('so_luong_ton <= muc_canh_bao')->count();
        $dsNguyenLieuSapHet = NguyenLieu::whereRaw('so_luong_ton <= muc_canh_bao')
            ->orderByRaw('so_luong_ton / NULLIF(muc_canh_bao, 0) ASC')
            ->get();

        // ===== DOANH THU 7 NGÀY =====
        $doanhThu7NgayRaw = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $doanhThu7NgayRaw[] = [
                'ngay'  => $date->format('d/m'),
                'thu'   => $date->locale('vi')->isoFormat('dd'),
                'total' => DonHang::whereDate('created_at', $date)
                    ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
                    ->sum('tong_tien'),
            ];
        }
        // Wrap vào Collection để view có thể dùng ->sum(), ->max() ...
        $doanhThu7Ngay = collect($doanhThu7NgayRaw);
        $maxDoanhThu   = $doanhThu7Ngay->max('total') ?: 1;


        // ===== ĐƠN HÀNG MỚI NHẤT =====
        $latestOrders = DonHang::with(['nguoiDung', 'nhanVien'])
            ->latest()
            ->limit(5)
            ->get();

        // ===== TOP SẢN PHẨM TUẦN =====
        $topProducts = DB::table('chi_tiet_don_hang')
            ->join('don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->join('san_pham', 'san_pham.id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->where('don_hang.created_at', '>=', $today->copy()->startOfWeek())
            ->whereNotIn('don_hang.trang_thai_don', ['huy', 'đã hủy'])
            ->select(
                'san_pham.id',
                'san_pham.ten_san_pham',
                DB::raw('SUM(chi_tiet_don_hang.so_luong) as tong_so_luong'),
                DB::raw('SUM(chi_tiet_don_hang.thanh_tien) as tong_doanh_thu')
            )
            ->groupBy('san_pham.id', 'san_pham.ten_san_pham')
            ->orderByDesc('tong_so_luong')
            ->limit(5)
            ->get();

        $maxSold = $topProducts->max('tong_so_luong') ?: 1;

        return view('manager.dashboard', compact(
            'doanhThuHomNay', 'doanhThuHomQua',
            'donHangHomNay',  'donHangHomQua',
            'khachHangMoi',   'nguyenLieuSapHet',
            'dsNguyenLieuSapHet',
            'doanhThu7Ngay',  'maxDoanhThu',
            'latestOrders',
            'topProducts',    'maxSold'
        ));
    }
}
