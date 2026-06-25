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
        if ($this->invalidDateRange($request)) {
            return redirect()->route('manager.reports.revenue')
                ->with('error', 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu.');
        }

        if ($this->exceedsSixMonths($request)) {
            return redirect()->route('manager.reports.revenue')
                ->with('error', 'Khoảng thời gian lọc không được vượt quá 6 tháng.');
        }

        return $this->buildReport($request, 'revenue');
    }

    /**
     * Người dùng chọn ngày kết thúc nhỏ hơn ngày bắt đầu (chỉ xét khi chọn cả 2 mốc).
     */
    private function invalidDateRange(Request $request): bool
    {
        if (! ($request->filled('from') && $request->filled('to'))) {
            return false;
        }

        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->endOfDay();

        return $to->lt($from);
    }

    /**
     * Khoảng lọc from→to có vượt quá 6 tháng không (chỉ xét khi người dùng tự chọn cả 2 mốc).
     */
    private function exceedsSixMonths(Request $request): bool
    {
        if (! ($request->filled('from') && $request->filled('to'))) {
            return false;
        }

        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->endOfDay();

        return $from->copy()->addMonths(6)->lt($to);
    }



    // =====================================================
    private function buildReport(Request $request, string $activeTab)
    {
        [$from, $to] = $this->resolveDateRange($request);

        // ===== STAT CARDS =====
        $tongDoanhThu = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->whereBetween('don_hang.created_at', [$from, $to])
            ->sum('chi_tiet_don_hang.tong_tien');

        $tongDon = DonHang::whereBetween('created_at', [$from, $to])->count();

        $giaTriTrungBinh = $tongDon > 0
            ? round($tongDoanhThu / $tongDon)
            : 0;

        // ===== DOANH THU THEO NGÀY =====
        $revenueByDay = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->selectRaw('DATE(don_hang.created_at) as ngay, SUM(chi_tiet_don_hang.tong_tien) as tong, COUNT(DISTINCT don_hang.id) as so_don')
            ->whereBetween('don_hang.created_at', [$from, $to])
            ->groupBy('ngay')
            ->orderBy('ngay')
            ->get();
        $maxRevenue = $revenueByDay->max('tong') ?: 1;

        // ===== STATUS COUNTS (by payment status) =====
        $statusCounts = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->whereBetween('don_hang.created_at', [$from, $to])
            ->select('chi_tiet_don_hang.trang_thai_thanh_toan', DB::raw('COUNT(DISTINCT don_hang.id) as cnt'))
            ->groupBy('chi_tiet_don_hang.trang_thai_thanh_toan')
            ->pluck('cnt', 'trang_thai_thanh_toan');

        return view('manager.reports.revenue', compact(
            'tongDoanhThu', 'tongDon', 'giaTriTrungBinh',
            'revenueByDay', 'maxRevenue',
            'statusCounts',
            'from', 'to', 'activeTab'
        ));
    }

    private function resolveDateRange(Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay(),
            ];
        }

        // Mặc định lọc 7 ngày gần nhất để biểu đồ có sức sống hơn
        return [
            Carbon::today()->subDays(6)->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }
    public function exportRevenueExcel(Request $request)
    {
        if ($this->invalidDateRange($request)) {
            return redirect()->route('manager.reports.revenue')
                ->with('error', 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu.');
        }

        if ($this->exceedsSixMonths($request)) {
            return redirect()->route('manager.reports.revenue')
                ->with('error', 'Khoảng thời gian lọc không được vượt quá 6 tháng.');
        }

        [$from, $to] = $this->resolveDateRange($request);

        $revenueByDay = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->selectRaw('DATE(don_hang.created_at) as ngay, SUM(chi_tiet_don_hang.tong_tien) as tong, COUNT(DISTINCT don_hang.id) as so_don')
            ->whereBetween('don_hang.created_at', [$from, $to])
            ->groupBy('ngay')
            ->orderBy('ngay')
            ->get();
            
        $tongDoanhThu = DonHang::join('chi_tiet_don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->whereBetween('don_hang.created_at', [$from, $to])
            ->sum('chi_tiet_don_hang.tong_tien');
        $tongDon = DonHang::whereBetween('created_at', [$from, $to])->count();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Doanh Thu');

        $sheet->setCellValue('A1', 'BÁO CÁO DOANH THU');
        $sheet->setCellValue('A2', 'Từ ngày: ' . $from->format('d/m/Y') . ' - Đến ngày: ' . $to->format('d/m/Y'));

        $sheet->setCellValue('A4', 'Ngày');
        $sheet->setCellValue('B4', 'Số lượng đơn');
        $sheet->setCellValue('C4', 'Doanh thu (VNĐ)');

        $sheet->getStyle('A4:C4')->getFont()->setBold(true);

        $row = 5;
        foreach ($revenueByDay as $item) {
            $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($item->ngay)->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $item->so_don);
            $sheet->setCellValue('C' . $row, $item->tong);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Tổng cộng');
        $sheet->setCellValue('B' . $row, $tongDon);
        $sheet->setCellValue('C' . $row, $tongDoanhThu);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'Bao_Cao_Doanh_Thu_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
