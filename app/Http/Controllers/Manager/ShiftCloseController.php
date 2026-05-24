<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CaLamViec;
use App\Models\ChiTieu;
use App\Models\ChotCa;
use App\Models\ThanhToan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShiftCloseController extends Controller
{
    public function index(Request $request)
    {
        $shiftGroups = $this->shiftGroups();
        $selectedShift = $this->resolveSelectedShift($shiftGroups, $request->input('ca_lam_viec_id'));
        $selectedShiftId = $selectedShift?->id;

        $summary = $this->buildSummary($selectedShiftId);

        return view('manager.shift-close.index', [
            'shiftGroups' => $shiftGroups,
            'selectedShift' => $selectedShift,
            'selectedShiftId' => $selectedShiftId,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ca_lam_viec_id' => 'required|exists:ca_lam_viec,id',
            'so_tien_dau_ca' => 'required|numeric|min:0',
            'ghi_chu' => 'nullable|string|max:500',
        ]);

        $selectedShiftId = (int) $request->input('ca_lam_viec_id');
        $summary = $this->buildSummary($selectedShiftId, (float) $request->input('so_tien_dau_ca'));

        if (! $summary['shift']) {
            throw ValidationException::withMessages([
                'ca_lam_viec_id' => 'Không tìm thấy ca làm việc cần chốt.',
            ]);
        }

        ChotCa::updateOrCreate(
            ['ca_lam_viec_id' => $selectedShiftId],
            [
                'nguoi_chot_id' => Auth::id(),
                'so_tien_dau_ca' => $summary['so_tien_dau_ca'],
                'chot_luc' => now(),
                'ghi_chu' => $request->filled('ghi_chu') ? trim((string) $request->input('ghi_chu')) : null,
            ]
        );

        return redirect()
            ->route('manager.shift-close.index', ['ca_lam_viec_id' => $selectedShiftId])
            ->with('success', 'Đã chốt ca thành công.');
    }

    private function buildSummary(?int $shiftId, ?float $startCash = null): array
    {
        if (! $shiftId) {
            return $this->emptySummary(null);
        }

        $shift = CaLamViec::find($shiftId);
        if (! $shift) {
            return $this->emptySummary(null);
        }

        [$startAt, $endAt] = $this->shiftRange($shift);

        $orderCash = $this->sumPaidOrders($startAt, $endAt, ['tiền mặt', 'tien_mat']);
        $orderTransfer = $this->sumPaidOrders($startAt, $endAt, ['chuyển khoản', 'chuyen_khoan']);

        $expenseCash = $this->sumExpenses($shiftId, ['tiền mặt']);
        $expenseTransfer = $this->sumExpenses($shiftId, ['chuyển khoản']);

        $existingClose = ChotCa::where('ca_lam_viec_id', $shiftId)->first();
        $startCashValue = $startCash !== null
            ? $startCash
            : (float) ($existingClose?->so_tien_dau_ca ?? 0);

        $cashAtCounter = $startCashValue + $orderCash - $expenseCash;
        $accountBalance = $orderTransfer - $expenseTransfer;

        return [
            'shift' => $shift,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'existing_close' => $existingClose,
            'so_tien_dau_ca' => $startCashValue,
            'tong_tien_mat' => $orderCash,
            'tong_tien_chuyen_khoan' => $orderTransfer,
            'tong_chi_mat' => $expenseCash,
            'tong_chi_chuyen_khoan' => $expenseTransfer,
            'so_tien_quay' => $cashAtCounter,
            'so_tien_tai_khoan' => $accountBalance,
        ];
    }

    private function emptySummary(?CaLamViec $shift): array
    {
        return [
            'shift' => $shift,
            'start_at' => null,
            'end_at' => null,
            'existing_close' => null,
            'so_tien_dau_ca' => 0,
            'tong_tien_mat' => 0,
            'tong_tien_chuyen_khoan' => 0,
            'tong_chi_mat' => 0,
            'tong_chi_chuyen_khoan' => 0,
            'so_tien_quay' => 0,
            'so_tien_tai_khoan' => 0,
        ];
    }

    private function shiftGroups(): Collection
    {
        return CaLamViec::query()
            ->selectRaw('MIN(id) as id, ngay_lam, ten_ca, gio_bat_dau, gio_ket_thuc')
            ->groupBy('ngay_lam', 'ten_ca', 'gio_bat_dau', 'gio_ket_thuc')
            ->orderBy('ngay_lam', 'desc')
            ->orderBy('gio_bat_dau')
            ->get();
    }

    private function resolveSelectedShift(Collection $groups, ?string $requestedId): ?object
    {
        if ($groups->isEmpty()) {
            return null;
        }

        if ($requestedId !== null && $requestedId !== '') {
            $match = $groups->firstWhere('id', (int) $requestedId);
            if ($match) {
                return $match;
            }
        }

        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $activeShift = $groups->first(function ($shift) use ($today, $currentTime) {
            $shiftDate = $shift->ngay_lam instanceof Carbon
                ? $shift->ngay_lam->toDateString()
                : (is_string($shift->ngay_lam) ? substr($shift->ngay_lam, 0, 10) : $shift->ngay_lam);

            return $shiftDate === $today &&
                   $shift->gio_bat_dau <= $currentTime &&
                   $shift->gio_ket_thuc >= $currentTime;
        });

        if ($activeShift) {
            return $activeShift;
        }

        return $groups->first();
    }

    private function shiftRange(CaLamViec $shift): array
    {
        $date = $shift->ngay_lam instanceof Carbon
            ? $shift->ngay_lam->format('Y-m-d')
            : (string) $shift->ngay_lam;

        $startAt = Carbon::parse($date . ' ' . $shift->gio_bat_dau);
        $endAt = Carbon::parse($date . ' ' . $shift->gio_ket_thuc);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt->addDay();
        }

        return [$startAt, $endAt];
    }

    private function sumPaidOrders(Carbon $startAt, Carbon $endAt, array $methods): float
    {
        return (float) ThanhToan::query()
            ->where('trang_thai', 'đã thanh toán')
            ->whereNotNull('thanh_toan_luc')
            ->whereBetween('thanh_toan_luc', [$startAt, $endAt])
            ->whereIn('phuong_thuc', $methods)
            ->sum('so_tien');
    }

    private function sumExpenses(int $shiftId, array $methods): float
    {
        $shift = CaLamViec::find($shiftId);
        if (!$shift) {
            return 0.0;
        }

        $allGroupIds = CaLamViec::where('ngay_lam', $shift->ngay_lam)
            ->where('ten_ca', $shift->ten_ca)
            ->where('gio_bat_dau', $shift->gio_bat_dau)
            ->where('gio_ket_thuc', $shift->gio_ket_thuc)
            ->pluck('id');

        $totalExpression = 'COALESCE(lich_su_kho.so_luong, 0) * COALESCE(lich_su_kho.gia_nhap, 0)';

        return (float) ChiTieu::query()
            ->whereIn('ca_lam_viec_id', $allGroupIds)
            ->whereIn('phuong_thuc_thanh_toan', $methods)
            ->leftJoin('lich_su_kho', 'lich_su_kho.id', '=', 'chi_tieu.lich_su_kho_id')
            ->leftJoin('nguyen_lieu', 'nguyen_lieu.id', '=', 'chi_tieu.nguyen_lieu_id')
            ->selectRaw("SUM({$totalExpression}) as tong_chi")
            ->value('tong_chi');
    }
}
