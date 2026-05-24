<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\NguoiDung;
use App\Models\NguyenLieu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Trang thống kê tổng hợp.
     * Tất cả tab đều dùng cùng view, dữ liệu thay đổi theo period.
     */
    public function revenue(Request $request)
    {
        return $this->buildReport($request, 'revenue');
    }

    public function orders(Request $request)
    {
        return $this->buildReport($request, 'orders');
    }

    public function products(Request $request)
    {
        return $this->buildReport($request, 'products');
    }

    public function staff(Request $request)
    {
        return $this->buildReport($request, 'staff');
    }

    public function inventory(Request $request)
    {
        return $this->buildReport($request, 'inventory');
    }

    // =====================================================
    private function buildReport(Request $request, string $activeTab)
    {
        [$from, $to] = $this->resolveDateRange($request);

        // ===== STAT CARDS =====
        $tongDoanhThu = DonHang::whereBetween('created_at', [$from, $to])
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->sum('tong_tien');

        $tongDon = DonHang::whereBetween('created_at', [$from, $to])->count();

        $giaTriTrungBinh = $tongDon > 0
            ? round($tongDoanhThu / $tongDon)
            : 0;

        // ===== DOANH THU THEO NGÀY =====
        $revenueByDay = DonHang::selectRaw('DATE(created_at) as ngay, SUM(tong_tien) as tong')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->groupBy('ngay')
            ->orderBy('ngay')
            ->get();
        $maxRevenue = $revenueByDay->max('tong') ?: 1;

        // ===== STATUS COUNTS =====
        $statusCounts = DonHang::whereBetween('created_at', [$from, $to])
            ->select('trang_thai_don', DB::raw('COUNT(*) as cnt'))
            ->groupBy('trang_thai_don')
            ->pluck('cnt', 'trang_thai_don');

        // ===== TOP PRODUCTS =====
        $topProducts = DB::table('chi_tiet_don_hang')
            ->join('don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->join('san_pham', 'san_pham.id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->join('danh_muc', 'danh_muc.id', '=', 'san_pham.danh_muc_id')
            ->whereBetween('don_hang.created_at', [$from, $to])
            ->whereNotIn('don_hang.trang_thai_don', ['huy', 'đã hủy'])
            ->select(
                'san_pham.id',
                'san_pham.ten_san_pham',
                'danh_muc.ten_danh_muc',
                DB::raw('SUM(chi_tiet_don_hang.so_luong) as tong_so_luong'),
                DB::raw('SUM(chi_tiet_don_hang.thanh_tien) as tong_doanh_thu')
            )
            ->groupBy('san_pham.id', 'san_pham.ten_san_pham', 'danh_muc.ten_danh_muc')
            ->orderByDesc('tong_so_luong')
            ->limit(10)
            ->get();
        $maxSold = $topProducts->max('tong_so_luong') ?: 1;

        // ===== PEAK HOURS =====
        $peakHours = DonHang::selectRaw('HOUR(created_at) as gio, COUNT(*) as cnt')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->groupBy('gio')
            ->orderBy('gio')
            ->pluck('cnt', 'gio')
            ->toArray();
        $maxHour = max($peakHours ?: [1]);

        // ===== STAFF PERFORMANCE =====
        $staffPerformance = DonHang::whereBetween('created_at', [$from, $to])
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->whereNotNull('nhan_vien_id')
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'don_hang.nhan_vien_id')
            ->select(
                'nguoi_dung.id',
                'nguoi_dung.ho_ten',
                DB::raw('COUNT(don_hang.id) as so_don'),
                DB::raw('SUM(don_hang.tong_tien) as tong_doanh_thu')
            )
            ->groupBy('nguoi_dung.id', 'nguoi_dung.ho_ten')
            ->orderByDesc('so_don')
            ->get();

        // ===== INVENTORY REPORT =====
        $inventoryBalanceExpression = "SUM(CASE
            WHEN lich_su_kho.loai_giao_dich IN ('nhap', 'nhập', 'nhap kho', 'nhập kho') THEN lich_su_kho.so_luong
            WHEN lich_su_kho.loai_giao_dich IN ('xuat', 'xuất', 'xuat kho', 'xuất kho') THEN -lich_su_kho.so_luong
            WHEN lich_su_kho.loai_giao_dich IN ('điều chỉnh', 'dieu chinh') THEN lich_su_kho.so_luong
            ELSE 0
        END)";

        $inventoryReport = NguyenLieu::query()
            ->leftJoin('lich_su_kho', 'lich_su_kho.nguyen_lieu_id', '=', 'nguyen_lieu.id')
            ->select('nguyen_lieu.id', 'nguyen_lieu.ten_nguyen_lieu', 'nguyen_lieu.don_vi_tinh')
            ->selectRaw("COALESCE({$inventoryBalanceExpression}, 0) as so_luong")
            ->groupBy('nguyen_lieu.id', 'nguyen_lieu.ten_nguyen_lieu', 'nguyen_lieu.don_vi_tinh')
            ->orderByRaw('COALESCE(' . $inventoryBalanceExpression . ', 0) <= 0 DESC')
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->get();

        return view('manager.reports.revenue', compact(
            'tongDoanhThu', 'tongDon', 'giaTriTrungBinh',
            'revenueByDay', 'maxRevenue',
            'statusCounts',
            'topProducts', 'maxSold',
            'peakHours', 'maxHour',
            'staffPerformance',
            'inventoryReport',
            'from', 'to', 'activeTab'
        ));
    }

    private function resolveDateRange(Request $request): array
    {
        $period = $request->get('period', 'today');

        if ($period === 'custom' && $request->filled('from') && $request->filled('to')) {
            return [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay(),
            ];
        }

        return match($period) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'year'  => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
        };
    }
}
