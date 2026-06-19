<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\ChotCa;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ShiftController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $fromDate = $request->input('from_date', now()->startOfWeek()->toDateString());
        $toDate = $request->input('to_date', now()->endOfWeek()->toDateString());

        $shifts = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', '>=', $fromDate)
            ->whereDate('ngay_lam', '<=', $toDate)
            ->orderBy('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        $attendanceMap = collect();
        if ($shifts->isNotEmpty()) {
            $attendanceMap = ChamCong::where('nguoi_dung_id', $user->id)
                ->whereIn('ca_lam_viec_id', $shifts->pluck('id'))
                ->latest('cham_cong_vao')
                ->get()
                ->groupBy('ca_lam_viec_id')
                ->map(fn($rows) => $rows->first());
        }

        $geoRequired = $this->attendanceService->geoEnforced($user->cuaHang);

        return view('staff.shifts.index', compact('shifts', 'attendanceMap', 'fromDate', 'toDate', 'geoRequired'));
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $shift = CaLamViec::where('nguoi_dung_id', $user->id)->findOrFail($id);
        $attendance = ChamCong::where('nguoi_dung_id', $user->id)
            ->where('ca_lam_viec_id', $shift->id)
            ->latest('cham_cong_vao')
            ->first();

        $shiftDate = $shift->ngay_lam?->format('Y-m-d') ?? now()->toDateString();
        $start = Carbon::parse($shiftDate . ' ' . $shift->gio_bat_dau);
        $end = Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }
        $now = now();

        $isInShiftTime = $now->between($start, $end);
        $canCheckin = $isInShiftTime && !$attendance;
        $canCheckout = $attendance && !$attendance->cham_cong_ra && $now->greaterThanOrEqualTo($start);

        $coworkers = CaLamViec::with('nguoiDung')
            ->whereDate('ngay_lam', $shift->ngay_lam)
            ->where('gio_bat_dau', $shift->gio_bat_dau)
            ->where('gio_ket_thuc', $shift->gio_ket_thuc)
            ->where('nguoi_dung_id', '!=', $user->id)
            ->get()
            ->map(fn($c) => $c->nguoiDung)
            ->filter();

        $geoRequired = $this->attendanceService->geoEnforced($user->cuaHang);

        return view('staff.shifts.show', compact('shift', 'attendance', 'canCheckin', 'canCheckout', 'isInShiftTime', 'start', 'end', 'coworkers', 'geoRequired'));
    }

    /**
     * Lấy toạ độ GPS gửi kèm từ form (nullable).
     *
     * @return array{0: ?float, 1: ?float}
     */
    private function requestCoords(Request $request): array
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        return [
            isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        ];
    }

    public function checkin(Request $request)
    {
        $request->validate([
            'ca_lam_viec_id' => 'required|exists:ca_lam_viec,id',
        ]);

        /** @var \App\Models\NguoiDung $user */
        $user = Auth::user();
        [$lat, $lng] = $this->requestCoords($request);

        // Chỉ cho check-in ca thuộc về chính nhân viên này.
        $shift = CaLamViec::where('id', (int) $request->ca_lam_viec_id)
            ->where('nguoi_dung_id', $user->id)
            ->firstOrFail();

        try {
            $this->attendanceService->assertWithinStore($user->cuaHang, $lat, $lng);
            $this->attendanceService->assertCheckinWindow($shift, now());
            $this->attendanceService->checkIn($user, $shift, 'manual');
        } catch (AttendanceException $e) {
            return back()->with($e->level === 'info' ? 'warning' : 'error', $e->getMessage());
        }

        return back()->with('success', 'Check-in thành công!');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:cham_cong,id',
        ]);

        /** @var \App\Models\NguoiDung $user */
        $user = Auth::user();
        [$lat, $lng] = $this->requestCoords($request);

        $attendance = ChamCong::where('id', $request->attendance_id)
            ->where('nguoi_dung_id', $user->id)
            ->firstOrFail();

        $shift = CaLamViec::find($attendance->ca_lam_viec_id);
        if (! $shift) {
            return back()->with('error', 'Không tìm thấy ca làm việc tương ứng.');
        }

        try {
            $this->attendanceService->assertWithinStore($user->cuaHang, $lat, $lng);
            $this->attendanceService->checkOut($user, $shift, 'manual');
        } catch (AttendanceException $e) {
            return back()->with($e->level === 'info' ? 'warning' : 'error', $e->getMessage());
        }

        return back()->with('success', 'Check-out thành công!');
    }

    public function startCash(Request $request)
    {
        $request->merge([
            'so_tien_dau_ca' => str_replace(',', '', $request->input('so_tien_dau_ca', ''))
        ]);

        $request->validate([
            'ca_lam_viec_id' => 'required|exists:ca_lam_viec,id',
            'so_tien_dau_ca' => 'required|numeric|min:0',
        ]);

        $selectedShiftId = (int) $request->input('ca_lam_viec_id');

        $existing = ChotCa::where('ca_lam_viec_id', $selectedShiftId)->first();
        if ($existing) {
            return back()->with('error', 'Ca này đã được khai báo tiền đầu ca.');
        }

        ChotCa::create([
            'ca_lam_viec_id' => $selectedShiftId,
            'nguoi_chot_id' => Auth::id(),
            'so_tien_dau_ca' => (float) $request->input('so_tien_dau_ca'),
        ]);

        return back()->with('success', 'Đã khai báo tiền đầu ca thành công.');
    }

    public function exportSchedule(Request $request)
    {
        $user = Auth::user();
        $referenceDate = Carbon::parse((string) $request->input('date', now()->toDateString()));
        $startDate = $referenceDate->copy()->startOfMonth()->startOfDay();
        $endDate = $referenceDate->copy()->endOfMonth()->endOfDay();

        $shifts = CaLamViec::query()
            ->where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', '>=', $startDate)
            ->whereDate('ngay_lam', '<=', $endDate)
            ->orderBy('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        $attendanceMap = collect();
        if ($shifts->isNotEmpty()) {
            $attendanceMap = ChamCong::query()
                ->where('nguoi_dung_id', $user->id)
                ->whereIn('ca_lam_viec_id', $shifts->pluck('id'))
                ->latest('cham_cong_vao')
                ->get()
                ->groupBy('ca_lam_viec_id')
                ->map(fn ($rows) => $rows->first());
        }

        $headers = [
            'STT',
            'Tên ca',
            'Ngày làm',
            'Giờ ca',
            'Check in',
            'Check out',
            'Thời gian làm việc (giờ)',
            'Ghi chú',
        ];

        $rows = [];
        foreach ($shifts as $index => $shift) {
            $attendance = $attendanceMap->get($shift->id);
            $checkIn = $attendance?->cham_cong_vao ? Carbon::parse($attendance->cham_cong_vao) : null;
            $checkOut = $attendance?->cham_cong_ra ? Carbon::parse($attendance->cham_cong_ra) : null;

            [$plannedStart, $plannedEnd] = $this->buildPlannedShiftWindow($shift);

            $workedHours = 0.0;
            if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
                $workedHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);
            } elseif ($checkIn) {
                $endPoint = now()->lessThan($plannedEnd) ? now() : $plannedEnd;
                if ($endPoint->greaterThan($checkIn)) {
                    $workedHours = round($checkIn->diffInMinutes($endPoint) / 60, 2);
                }
            }

            $note = $this->buildShiftDeviationNote($shift, $attendance);

            $rows[] = [
                $index + 1,
                $shift->ten_ca,
                optional($shift->ngay_lam)->format('d/m/Y'),
                $shift->gio_bat_dau . ' - ' . $shift->gio_ket_thuc,
                $checkIn ? $checkIn->format('d/m/Y H:i') : '—',
                $checkOut ? $checkOut->format('d/m/Y H:i') : '—',
                number_format($workedHours, 2, '.', ''),
                $note !== '' ? $note : '—',
            ];
        }

        $filename = 'lich-ca-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.xlsx';
        return $this->downloadExcel($headers, $rows, $filename);
    }

    private function buildPlannedShiftWindow(CaLamViec $shift): array
    {
        $shiftDate = $shift->ngay_lam?->format('Y-m-d') ?? now()->toDateString();
        $plannedStart = Carbon::parse($shiftDate . ' ' . $shift->gio_bat_dau);
        $plannedEnd = Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);

        if ($plannedEnd->lessThanOrEqualTo($plannedStart)) {
            $plannedEnd->addDay();
        }

        return [$plannedStart, $plannedEnd];
    }

            private function formatMinutesToHours(int $minutes): string
    {
        $h = (int) floor($minutes / 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) {
            return "{$h} giờ {$m} phút";
        }
        if ($h > 0) {
            return "{$h} giờ";
        }
        if ($m > 0) {
            return "{$m} phút";
        }
        return "0 phút";
    }

    private function buildShiftDeviationNote(CaLamViec $shift, ?ChamCong $attendance): string
    {
        [$plannedStart, $plannedEnd] = $this->buildPlannedShiftWindow($shift);

        $notes = [];
        $checkIn = $attendance?->cham_cong_vao ? Carbon::parse($attendance->cham_cong_vao) : null;
        $checkOut = $attendance?->cham_cong_ra ? Carbon::parse($attendance->cham_cong_ra) : null;

        if ($checkIn) {
            $diffMinCi = (int) round($checkIn->diffInSeconds($plannedStart, false) / 60);
            if ($diffMinCi > 0) {
                $notes[] = 'Chấm công vào sớm ' . $this->formatMinutesToHours($diffMinCi);
            } elseif ($diffMinCi < 0) {
                $notes[] = 'Chấm công vào muộn ' . $this->formatMinutesToHours(abs($diffMinCi));
            }
        } else {
            $notes[] = 'Chưa chấm công vào';
        }

        if ($checkOut) {
            $diffMinCo = (int) round($checkOut->diffInSeconds($plannedEnd, false) / 60);
            if ($diffMinCo > 0) {
                $notes[] = 'Chấm công ra sớm ' . $this->formatMinutesToHours($diffMinCo);
            } elseif ($diffMinCo < 0) {
                $notes[] = 'Chấm công ra muộn ' . $this->formatMinutesToHours(abs($diffMinCo));
            }
        }

        return implode(' | ', $notes);
    }

    private function downloadExcel(array $headers, array $rows, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headers, null, 'A1');
        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'staff_shift_excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download(
            $tempFile,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
