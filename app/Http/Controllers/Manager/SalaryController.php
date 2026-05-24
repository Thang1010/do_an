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
        $search = trim((string) $request->input('search', ''));
        $filterRole = $request->input('vai_tro', '');
        $thang = (int) ($request->input('thang') ?: now()->month);
        $nam = (int) ($request->input('nam') ?: now()->year);

        [$periodStart, $periodEnd] = $this->salaryPeriod($thang, $nam);

        $users = NguoiDung::query()
            ->whereIn('vai_tro', ['nhân viên', 'quản lý'])
            ->where('trang_thai', 'hoạt động')
            ->with(['hoSoNhanVien.chucVu', 'hoSoQuanLy.chucVu'])
            ->when($filterRole !== '', fn (Builder $q) => $q->where('vai_tro', $filterRole))
            ->when($search !== '', fn (Builder $q) => $q->where('ho_ten', 'like', "%{$search}%"))
            ->orderBy('ho_ten')
            ->paginate(20)
            ->withQueryString();

        $totalRevenue = $this->totalRevenue($periodStart, $periodEnd);

        $salaryData = $users->through(function (NguoiDung $user) use ($periodStart, $periodEnd, $totalRevenue) {
            return $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue);
        });

        return view('manager.salary.index', [
            'users' => $salaryData,
            'search' => $search,
            'filterRole' => $filterRole,
            'thang' => $thang,
            'nam' => $nam,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
        ]);
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
     * Form sửa lương.
     */
    public function edit(Request $request, int $id)
    {
        $user = NguoiDung::with(['hoSoNhanVien.chucVu', 'hoSoQuanLy.chucVu'])->findOrFail($id);

        $thang = (int) ($request->input('thang') ?: now()->month);
        $nam = (int) ($request->input('nam') ?: now()->year);

        [$periodStart, $periodEnd] = $this->salaryPeriod($thang, $nam);
        $totalRevenue = $this->totalRevenue($periodStart, $periodEnd);
        $salaryRow = $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue);

        return view('manager.salary.edit', [
            'user' => $user,
            'salaryRow' => $salaryRow,
            'thang' => $thang,
            'nam' => $nam,
        ]);
    }

    /**
     * Lưu thay đổi lương.
     */
    public function update(Request $request, int $id)
    {
        $user = NguoiDung::with(['hoSoNhanVien', 'hoSoQuanLy'])->findOrFail($id);
        $profile = $user->isNhanVien() ? $user->hoSoNhanVien : $user->hoSoQuanLy;

        if (! $profile) {
            return back()->with('error', 'Không tìm thấy hồ sơ nhân sự.');
        }

        $loaiHinh = $profile->loai_hinh_lam_viec ?? 'toàn thời gian';

        if ($loaiHinh === 'bán thời gian') {
            $request->validate(['luong_theo_gio' => 'required|numeric|min:0']);
            $profile->update(['luong_theo_gio' => $request->input('luong_theo_gio')]);
        } else {
            $request->validate(['luong_co_ban' => 'required|numeric|min:0']);
            $profile->update(['luong_co_ban' => $request->input('luong_co_ban')]);
        }

        return redirect()
            ->route('manager.salary.index')
            ->with('success', 'Đã cập nhật lương cho ' . $user->ho_ten . '.');
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
            ->orderBy('ho_ten')
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
            $sheet->setCellValue([8, $rowIndex], round($row['tong_gio'], 2));
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
     * Xác định kỳ lương: 15/tháng → 15/tháng+1.
     */
    private function salaryPeriod(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 15)->startOfDay();
        $end = $start->copy()->addMonth()->startOfDay();

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

    /**
     * Tổng giờ làm việc của user trong kỳ.
     */
    private function totalHoursWorked(int $userId, Carbon $start, Carbon $end): float
    {
        $records = ChamCong::query()
            ->where('nguoi_dung_id', $userId)
            ->whereNotNull('check_in_luc')
            ->whereNotNull('check_out_luc')
            ->whereHas('caLamViec', function (Builder $q) use ($start, $end) {
                $q->whereBetween('ngay_lam', [$start->toDateString(), $end->toDateString()]);
            })
            ->get();

        $totalMinutes = $records->sum(function (ChamCong $record) {
            $checkIn = Carbon::parse($record->check_in_luc);
            $checkOut = Carbon::parse($record->check_out_luc);

            return $checkOut->greaterThan($checkIn) ? $checkIn->diffInMinutes($checkOut) : 0;
        });

        return round($totalMinutes / 60, 2);
    }

    /**
     * Tính lương cho 1 user.
     */
    private function calculateSalary(NguoiDung $user, string $loaiHinh, ?float $luongCoBan, ?float $luongTheoGio, float $totalHours, float $totalRevenue): float
    {
        if ($loaiHinh === 'bán thời gian') {
            return ($luongTheoGio ?? 0) * $totalHours;
        }

        // Toàn thời gian
        $base = $luongCoBan ?? 0;
        $commissionRate = $user->isQuanLy() ? 0.01 : 0.005;

        return $base + ($totalRevenue * $commissionRate);
    }

    /**
     * Build 1 hàng dữ liệu lương cho user.
     */
    private function buildUserSalaryRow(NguoiDung $user, Carbon $periodStart, Carbon $periodEnd, float $totalRevenue): array
    {
        $isNhanVien = $user->isNhanVien();
        $profile = $isNhanVien ? $user->hoSoNhanVien : $user->hoSoQuanLy;

        $loaiHinh = $profile->loai_hinh_lam_viec ?? 'toàn thời gian';
        $luongCoBan = $profile->luong_co_ban ?? null;
        $luongTheoGio = $profile->luong_theo_gio ?? null;
        $chucVu = $profile?->chucVu?->ten_chuc_vu ?? '—';

        $totalHours = $this->totalHoursWorked($user->id, $periodStart, $periodEnd);
        $totalSalary = $this->calculateSalary($user, $loaiHinh, $luongCoBan, $luongTheoGio, $totalHours, $totalRevenue);

        return [
            'id' => $user->id,
            'ho_ten' => $user->ho_ten,
            'vai_tro' => $user->vai_tro,
            'chuc_vu' => $chucVu,
            'loai_hinh' => $loaiHinh,
            'luong_co_ban' => $luongCoBan,
            'luong_theo_gio' => $luongTheoGio,
            'tong_gio' => $totalHours,
            'tong_luong' => $totalSalary,
        ];
    }
}
