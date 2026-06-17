<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ChamCong;
use App\Models\NguoiDung;
use App\Models\ThanhToan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryController extends Controller
{
    /**
     * Danh sách bảng lương.
     */
    public function index(Request $request)
    {
        $filterRole = $request->input('vai_tro');
        $thang = (int) ($request->input('thang') ?: now()->month);
        $nam = (int) ($request->input('nam') ?: now()->year);

        [$periodStart, $periodEnd] = $this->salaryPeriod($thang, $nam);

        $users = NguoiDung::query()
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->where('trang_thai', 'hoạt động')
            ->with(['hoSoNhanVien.chucVu', 'hoSoQuanLy.chucVu'])
            ->when(!empty($filterRole), fn (Builder $q) => $q->where('vai_tro', $filterRole))
            ->orderBy('email')
            ->paginate(20)
            ->withQueryString();

        $totalRevenue = $this->totalRevenue($periodStart, $periodEnd);

        try {
            $salaryData = $users->through(function (NguoiDung $user) use ($periodStart, $periodEnd, $totalRevenue) {
                return $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue);
            });
        } catch (\Throwable $e) {
            dd('Error in logic: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        }

        try {
            return view('manager.salary.index', [
                'users' => $salaryData,
                'filterRole' => $filterRole,
                'thang' => $thang,
                'nam' => $nam,
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
            ])->render();
        } catch (\Throwable $e) {
            dd('View Error: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * Chi tiết ca làm việc của user trong kỳ lương.
     */
    public function show(Request $request, int $id)
    {
        $user = NguoiDung::with(['hoSoNhanVien.chucVu', 'hoSoQuanLy.chucVu'])->findOrFail($id);
        $thang = (int) ($request->input('thang') ?: now()->month);
        $nam = (int) ($request->input('nam') ?: now()->year);

        [$periodStart, $periodEnd] = $this->salaryPeriod($thang, $nam);
        $totalRevenue = $this->totalRevenue($periodStart, $periodEnd);
        $salaryRow = $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue);

        $attendances = ChamCong::query()
            ->where('nguoi_dung_id', $user->id)
            ->with('caLamViec')
            ->whereHas('caLamViec', function (Builder $q) use ($periodStart, $periodEnd) {
                $q->whereBetween('ngay_lam', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            })
            ->get()
            ->sortBy(fn (ChamCong $a) => optional($a->caLamViec)->ngay_lam);

        return view('manager.salary.show', [
            'user' => $user,
            'salaryRow' => $salaryRow,
            'attendances' => $attendances,
            'thang' => $thang,
            'nam' => $nam,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
        ]);
    }


    /**
     * Xuất bảng lương ra Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $thang = (int) ($request->input('thang') ?: now()->month);
        $nam = (int) ($request->input('nam') ?: now()->year);

        [$periodStart, $periodEnd] = $this->salaryPeriod($thang, $nam);
        $totalRevenue = $this->totalRevenue($periodStart, $periodEnd);

        $users = NguoiDung::query()
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->where('trang_thai', 'hoạt động')
            ->with(['hoSoNhanVien.chucVu', 'hoSoQuanLy.chucVu'])
            ->orderBy('email')
            ->get();

        $rows = $users->map(fn (NguoiDung $user) => $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bảng lương');

        $headers = ['STT', 'Họ tên', 'Vai trò', 'Chức vụ', 'Loại hình', 'Lương cơ bản', 'Lương theo giờ', 'Tổng giờ làm', 'Tổng lương'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($rows as $i => $row) {
            $sheet->setCellValue([1, $rowIndex], $i + 1);
            $sheet->setCellValue([2, $rowIndex], $row['ho_ten']);
            $sheet->setCellValue([3, $rowIndex], $row['vai_tro']);
            $sheet->setCellValue([4, $rowIndex], $row['chuc_vu']);
            $sheet->setCellValue([5, $rowIndex], $row['loai_hinh']);
            $sheet->setCellValue([6, $rowIndex], $row['luong_co_ban']);
            $sheet->setCellValue([7, $rowIndex], $row['luong_theo_gio']);
            $sheet->setCellValue([8, $rowIndex], $row['tong_gio_format']);
            $sheet->setCellValue([9, $rowIndex], round($row['tong_luong'], 0));
            $rowIndex++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "bang-luong-thang-{$thang}-{$nam}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Xác định kỳ lương: Ngày đầu tháng → Ngày cuối tháng của tháng đó.
     */
    private function salaryPeriod(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    /**
     * Tổng doanh thu đã thanh toán trong kỳ.
     */
    private function totalRevenue(Carbon $start, Carbon $end): float
    {
        return (float) ThanhToan::query()
            ->where('trang_thai', 'đã thanh toán')
            ->whereNotNull('thanh_toan_luc')
            ->whereBetween('thanh_toan_luc', [$start, $end])
            ->sum('so_tien');
    }

    private function totalMinutesWorked(int $userId, Carbon $start, Carbon $end): float
    {
        $user = NguoiDung::find($userId);
        $isManager = $user && $user->vai_tro === 'quản lý';

        if ($isManager) {
            $shifts = \App\Models\CaLamViec::query()
                ->where('nguoi_dung_id', $userId)
                ->whereBetween('ngay_lam', [$start->toDateString(), $end->toDateString()])
                ->get();
            
            return $shifts->sum(function ($shift) {
                $shiftDate = $shift->ngay_lam instanceof \Carbon\CarbonInterface
                    ? $shift->ngay_lam->format('Y-m-d')
                    : \Carbon\Carbon::parse($shift->ngay_lam)->format('Y-m-d');
                $shiftStart = Carbon::parse($shiftDate . ' ' . $shift->gio_bat_dau);
                $shiftEnd = Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);
                if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
                    $shiftEnd->addDay();
                }
                return $shiftStart->diffInMinutes($shiftEnd);
            });
        }

        $records = ChamCong::query()
            ->where('nguoi_dung_id', $userId)
            ->whereNotNull('cham_cong_vao')
            ->whereNotNull('cham_cong_ra')
            ->whereHas('caLamViec', function (Builder $q) use ($start, $end) {
                $q->whereBetween('ngay_lam', [$start->toDateString(), $end->toDateString()]);
            })
            ->get();

        $totalMinutes = $records->sum(function (ChamCong $record) {
            $checkIn = Carbon::parse($record->cham_cong_vao);
            $checkOut = Carbon::parse($record->cham_cong_ra);

            return $checkOut->greaterThan($checkIn) ? $checkIn->diffInMinutes($checkOut) : 0;
        });

        return $totalMinutes;
    }

    private function formatMinutesToHours(float $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) {
            return "{$h} giờ {$m} phút";
        }
        if ($h > 0) {
            return "{$h} giờ";
        }
        return "{$m} phút";
    }

    /**
     * Tính lương cho 1 user.
     */
    private function calculateSalary(NguoiDung $user, string $loaiHinh, ?float $luongCoBan, ?float $luongTheoGio, float $totalMinutes, float $totalRevenue): float
    {
        $base = $luongCoBan ?? 0;
        return $base + (($totalMinutes / 60) * ($luongTheoGio ?? 0));
    }

    /**
     * Build 1 hàng dữ liệu lương cho user.
     */
    private function buildUserSalaryRow(NguoiDung $user, Carbon $periodStart, Carbon $periodEnd, float $totalRevenue): array
    {
        $isNhanVien = $user->isNhanVien();
        $profile = $isNhanVien ? $user->hoSoNhanVien : $user->hoSoQuanLy;

        $loaiHinh = $profile?->chucVu?->loai_hinh_lam_viec ?? 'toàn thời gian';
        $luongCoBan = $profile?->chucVu?->luong_co_ban ?? 0;
        $luongTheoGio = $profile?->chucVu?->luong_theo_gio ?? 0;
        $chucVu = $profile?->chucVu?->ten_chuc_vu ?? '—';

                $totalMinutes = $this->totalMinutesWorked($user->id, $periodStart, $periodEnd);
        $totalSalary = $this->calculateSalary($user, $loaiHinh, $luongCoBan, $luongTheoGio, $totalMinutes, $totalRevenue);

        return [
            'id' => $user->id,
            'ho_ten' => $user->ho_ten ?? $user->email,
            'vai_tro' => $user->vai_tro,
            'chuc_vu' => $chucVu,
            'loai_hinh' => $loaiHinh,
            'luong_co_ban' => $luongCoBan,
            'luong_theo_gio' => $luongTheoGio,
            'tong_phut' => $totalMinutes,
            'tong_gio_format' => $this->formatMinutesToHours($totalMinutes),
            'tong_luong' => $totalSalary,
        ];
    }
}
