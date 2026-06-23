<?php

namespace App\Http\Controllers\Manager;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\NguoiDung;
use App\Models\NguyenLieu;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // ===== STAT CARDS =====
        $doanhThuHomNay = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->whereDate('don_hang.created_at', $today)
            ->sum('chi_tiet_don_hang.tong_tien');

        $doanhThuHomQua = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->whereDate('don_hang.created_at', $today->copy()->subDay())
            ->sum('chi_tiet_don_hang.tong_tien');

        $donHangHomNay = DonHang::whereDate('created_at', $today)->count();
        $donHangHomQua = DonHang::whereDate('created_at', $today->copy()->subDay())->count();

        $khachHangMoi = NguoiDung::whereDate('created_at', $today)
            ->where('vai_tro', 'khách hàng')
            ->count();

        // Nguyên liệu sắp hết / hết hàng (tồn kho làm được <= 3 cốc)
        $balanceExpr = TransactionType::stockBalanceExpression('lich_su_kho');
        $dsNguyenLieuSapHet = NguyenLieu::query()
            ->dangSuDung()
            ->leftJoin('lich_su_kho', 'lich_su_kho.nguyen_lieu_id', '=', 'nguyen_lieu.id')
            ->select('nguyen_lieu.id', 'nguyen_lieu.ten_nguyen_lieu', 'nguyen_lieu.don_vi_tinh')
            ->selectRaw("COALESCE({$balanceExpr}, 0) as so_luong")
            ->selectRaw('(SELECT MAX(ctsp.so_luong_can) FROM cong_thuc_san_pham ctsp WHERE ctsp.nguyen_lieu_id = nguyen_lieu.id) as max_tieu_hao')
            ->groupBy('nguyen_lieu.id', 'nguyen_lieu.ten_nguyen_lieu', 'nguyen_lieu.don_vi_tinh')
            ->havingRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) <= 3')
            ->orderByRaw('CASE WHEN FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) <= 0 THEN 0 ELSE 1 END')
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->get();
        $nguyenLieuSapHet = $dsNguyenLieuSapHet->count();

        // ===== DOANH THU TRONG TUẦN =====
        $startOfWeek = $today->copy()->startOfWeek(); // Thứ Hai
        $doanhThu7NgayRaw = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $doanhThu7NgayRaw[] = [
                'ngay'  => $date->format('d/m'),
                'thu'   => $date->locale('vi')->isoFormat('dd'),
                'total' => DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
                    ->whereDate('don_hang.created_at', $date)
                    ->sum('chi_tiet_don_hang.tong_tien'),
                'is_today' => $date->isToday(),
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
