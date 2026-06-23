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
            ->when(!empty($filterRole), fn(Builder $q) => $q->where('vai_tro', $filterRole))
            ->orderBy('email')
            ->paginate(20)
            ->withQueryString();

        $totalRevenue = $this->totalRevenue($periodStart, $periodEnd);

        $roleMap = $users->pluck('vai_tro', 'id')->toArray();
        $minutesMap = $this->calculateMinutesMap($roleMap, $periodStart, $periodEnd);

        $salaryData = $users->through(function (NguoiDung $user) use ($periodStart, $periodEnd, $totalRevenue, $minutesMap) {
            $mins = $minutesMap[$user->id] ?? 0;
            return $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue, $mins);
        });

        return view('manager.salary.index', [
            'users' => $salaryData,
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
        $mins = $this->calculateMinutesMap([$user->id => $user->vai_tro], $periodStart, $periodEnd)[$user->id] ?? 0;
        $salaryRow = $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue, $mins);

        $attendances = collect();
        $managerShifts = collect();

        if ($user->vai_tro === 'quản lý') {
            $managerShifts = \App\Models\CaLamViec::query()
                ->where('nguoi_dung_id', $user->id)
                ->whereBetween('ngay_lam', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orderBy('ngay_lam')
                ->get();
        } else {
            $attendances = ChamCong::query()
                ->where('nguoi_dung_id', $user->id)
                ->with('caLamViec')
                ->whereHas('caLamViec', function (Builder $q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('ngay_lam', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                })
                ->get()
                ->sortBy(fn(ChamCong $a) => optional($a->caLamViec)->ngay_lam);
        }

        return view('manager.salary.show', [
            'user' => $user,
            'salaryRow' => $salaryRow,
            'attendances' => $attendances,
            'managerShifts' => $managerShifts,
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

        $roleMap = $users->pluck('vai_tro', 'id')->toArray();
        $minutesMap = $this->calculateMinutesMap($roleMap, $periodStart, $periodEnd);

        $rows = $users->map(fn(NguoiDung $user) => $this->buildUserSalaryRow($user, $periodStart, $periodEnd, $totalRevenue, $minutesMap[$user->id] ?? 0));

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

    /**
     * Tính tổng số phút làm việc bằng SQL Aggregation cho một tập danh sách Users.
     * Giải quyết N+1 Query.
     *
     * @param array<int, string> $userRoles Map [user_id => vai_tro] đã có sẵn từ caller.
     */
    private function calculateMinutesMap(array $userRoles, Carbon $start, Carbon $end): array
    {
        if (empty($userRoles)) return [];

        $userIds = array_keys($userRoles);

        // 1. Quản lý (dựa trên ca làm việc)
        $managerIds = collect($userRoles)->filter(fn($r) => $r === 'quản lý')->keys()->toArray();
        $managerMinutes = [];
        if (!empty($managerIds)) {
            $managerMinutes = \App\Models\CaLamViec::query()
                ->whereIn('nguoi_dung_id', $managerIds)
                ->whereBetween('ngay_lam', [$start->toDateString(), $end->toDateString()])
                ->select('nguoi_dung_id')
                ->selectRaw('SUM(MOD(TIME_TO_SEC(TIMEDIFF(gio_ket_thuc, gio_bat_dau)) + 86400, 86400) / 60) as tong_phut')
                ->groupBy('nguoi_dung_id')
                ->pluck('tong_phut', 'nguoi_dung_id')
                ->toArray();
        }

        // 2. Nhân viên (dựa trên chấm công)
        $staffIds = collect($userRoles)->filter(fn($r) => $r !== 'quản lý')->keys()->toArray();
        $staffMinutes = [];
        if (!empty($staffIds)) {
            $staffMinutes = ChamCong::query()
                ->whereIn('nguoi_dung_id', $staffIds)
                ->whereNotNull('cham_cong_vao')
                ->whereNotNull('cham_cong_ra')
                ->whereHas('caLamViec', function (Builder $q) use ($start, $end) {
                    $q->whereBetween('ngay_lam', [$start->toDateString(), $end->toDateString()]);
                })
                ->select('nguoi_dung_id')
                // Dùng IF để tránh tính số âm nếu giờ ra < giờ vào do lỗi dữ liệu
                ->selectRaw('SUM(IF(cham_cong_ra > cham_cong_vao, TIMESTAMPDIFF(MINUTE, cham_cong_vao, cham_cong_ra), 0)) as tong_phut')
                ->groupBy('nguoi_dung_id')
                ->pluck('tong_phut', 'nguoi_dung_id')
                ->toArray();
        }

        // 3. Gắn kết quả tương ứng với vai trò
        $map = [];
        foreach ($userIds as $id) {
            $role = $userRoles[$id] ?? 'nhân viên';
            if ($role === 'quản lý') {
                $map[$id] = (float)($managerMinutes[$id] ?? 0);
            } else {
                $map[$id] = (float)($staffMinutes[$id] ?? 0);
            }
        }

        return $map;
    }

    private function formatMinutesToHours(float $minutes): string
    {
        $total = (int) round($minutes);
        $h = intdiv($total, 60);
        $m = $total % 60;
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
    private function buildUserSalaryRow(NguoiDung $user, Carbon $periodStart, Carbon $periodEnd, float $totalRevenue, float $totalMinutes): array
    {
        $isNhanVien = $user->isNhanVien();
        $profile = $isNhanVien ? $user->hoSoNhanVien : $user->hoSoQuanLy;

        $loaiHinh = $profile?->chucVu?->loai_hinh_lam_viec ?? 'toàn thời gian';
        $luongCoBan = $profile?->chucVu?->luong_co_ban ?? 0;
        $luongTheoGio = $profile?->chucVu?->luong_theo_gio ?? 0;
        $chucVu = $profile?->chucVu?->ten_chuc_vu ?? '—';

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
