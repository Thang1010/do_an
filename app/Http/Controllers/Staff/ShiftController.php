<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Notifications\ShiftCheckoutNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->toDateString());

        $shifts = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', $date)
            ->orderBy('gio_bat_dau')
            ->get();

        $attendanceMap = collect();
        if ($shifts->isNotEmpty()) {
            $attendanceMap = ChamCong::where('nguoi_dung_id', $user->id)
                ->whereIn('ca_lam_viec_id', $shifts->pluck('id'))
                ->latest('check_in_luc')
                ->get()
                ->groupBy('ca_lam_viec_id')
                ->map(fn($rows) => $rows->first());
        }

        return view('staff.shifts.index', compact('shifts', 'attendanceMap', 'date'));
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $shift = CaLamViec::where('nguoi_dung_id', $user->id)->findOrFail($id);
        $attendance = ChamCong::where('nguoi_dung_id', $user->id)
            ->where('ca_lam_viec_id', $shift->id)
            ->latest('check_in_luc')
            ->first();

        $shiftDate = $shift->ngay_lam?->format('Y-m-d') ?? now()->toDateString();
        $start = Carbon::parse($shiftDate . ' ' . $shift->gio_bat_dau);
        $end = Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);
        $now = now();

        $isInShiftTime = $now->between($start, $end);
        $canCheckin = $isInShiftTime && !$attendance;
        $canCheckout = $attendance && !$attendance->check_out_luc && $now->greaterThanOrEqualTo($start);

        return view('staff.shifts.show', compact('shift', 'attendance', 'canCheckin', 'canCheckout', 'isInShiftTime'));
    }

    public function checkin(Request $request)
    {
        $request->validate([
            'ca_lam_viec_id' => 'required|exists:ca_lam_viec,id',
        ]);

        $user = Auth::user();
        $shiftId = (int) $request->ca_lam_viec_id;

        // Check if already checked in
        $existing = ChamCong::where('nguoi_dung_id', $user->id)
            ->where('ca_lam_viec_id', $shiftId)
            ->whereNull('check_out_luc')
            ->first();

        if ($existing) {
            return back()->with('warning', 'Bạn đã check-in ca này rồi.');
        }

        ChamCong::create([
            'nguoi_dung_id' => $user->id,
            'ca_lam_viec_id' => $shiftId,
            'check_in_luc' => now(),
        ]);

        return back()->with('success', 'Check-in thành công!');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:cham_cong,id',
        ]);

        $attendance = ChamCong::where('id', $request->attendance_id)
            ->where('nguoi_dung_id', Auth::id())
            ->whereNull('check_out_luc')
            ->firstOrFail();

        $attendance->update([
            'check_out_luc' => now(),
        ]);

        $shift = CaLamViec::find($attendance->ca_lam_viec_id);
        if ($shift) {
            $note = $this->buildShiftDeviationNote($shift, $attendance);
            $request->user()?->notify(new ShiftCheckoutNotification($shift, $note));
        }

        return back()->with('success', 'Check-out thành công!');
    }

    public function exportSchedule(Request $request)
    {
        $user = Auth::user();
        $referenceDate = Carbon::parse((string) $request->input('date', now()->toDateString()));
        $startDate = $referenceDate->copy()->day(15)->startOfDay();
        $endDate = $startDate->copy()->addMonth()->day(15)->endOfDay();

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
                ->latest('check_in_luc')
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
            $checkIn = $attendance?->check_in_luc ? Carbon::parse($attendance->check_in_luc) : null;
            $checkOut = $attendance?->check_out_luc ? Carbon::parse($attendance->check_out_luc) : null;

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

    private function buildShiftDeviationNote(CaLamViec $shift, ?ChamCong $attendance): string
    {
        [$plannedStart, $plannedEnd] = $this->buildPlannedShiftWindow($shift);

        $notes = [];
        $checkIn = $attendance?->check_in_luc ? Carbon::parse($attendance->check_in_luc) : null;
        $checkOut = $attendance?->check_out_luc ? Carbon::parse($attendance->check_out_luc) : null;

        if ($checkIn && $checkIn->greaterThan($plannedStart)) {
            $notes[] = 'Check in muộn';
        }

        if ($checkOut && $checkOut->lessThan($plannedEnd)) {
            $notes[] = 'Check out sớm';
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
