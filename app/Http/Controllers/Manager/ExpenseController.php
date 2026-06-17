<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CaLamViec;
use App\Models\ChiTieu;
use App\Models\LichSuKho;
use App\Models\NguyenLieu;
use App\Traits\NormalizesPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    use NormalizesPayment;
    public function index(Request $request)
    {
        $shiftGroups = $this->shiftGroups();
        
        $filterDate = trim((string) $request->input('ngay_lam', ''));
        $requestedShiftId = $request->input('ca_lam_viec_id');

        $selectedShiftId = null;
        $selectedShift = null;
        $allGroupIds = collect();

        if ($requestedShiftId) {
            $selectedShift = $groupsMatch = $shiftGroups->firstWhere('id', (int) $requestedShiftId);
            $selectedShiftId = $selectedShift?->id;
            
            if ($selectedShiftId) {
                $shift = CaLamViec::find($selectedShiftId);
                $allGroupIds = $shift ? CaLamViec::where('ngay_lam', $shift->ngay_lam)
                    ->where('ten_ca', $shift->ten_ca)
                    ->where('gio_bat_dau', $shift->gio_bat_dau)
                    ->where('gio_ket_thuc', $shift->gio_ket_thuc)
                    ->pluck('id') : collect([$selectedShiftId]);
            }
        } elseif ($filterDate !== '') {
            $allGroupIds = CaLamViec::whereDate('ngay_lam', $filterDate)->pluck('id');
        } else {
            $selectedShift = $this->resolveSelectedShift($shiftGroups, null);
            $selectedShiftId = $selectedShift?->id;
            
            if ($selectedShiftId) {
                $shift = CaLamViec::find($selectedShiftId);
                $allGroupIds = $shift ? CaLamViec::where('ngay_lam', $shift->ngay_lam)
                    ->where('ten_ca', $shift->ten_ca)
                    ->where('gio_bat_dau', $shift->gio_bat_dau)
                    ->where('gio_ket_thuc', $shift->gio_ket_thuc)
                    ->pluck('id') : collect([$selectedShiftId]);
            }
        }

        $expenses = ChiTieu::query()
            ->with(['nguoiTao', 'nguyenLieu', 'lichSuKho'])
            ->when($allGroupIds->isNotEmpty(), function (Builder $q) use ($allGroupIds) {
                $q->whereIn('chi_tieu.ca_lam_viec_id', $allGroupIds);
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'tong_tien_mat' => 0,
            'tong_tien_chuyen_khoan' => 0,
            'tong_chi' => 0,
        ];

        if ($allGroupIds->isNotEmpty()) {
            $totalExpression = 'COALESCE(lich_su_kho.so_luong, 0) * COALESCE(lich_su_kho.gia_nhap, 0)';

            $summaryRow = ChiTieu::query()
                ->whereIn('chi_tieu.ca_lam_viec_id', $allGroupIds)
                ->leftJoin('lich_su_kho', 'lich_su_kho.id', '=', 'chi_tieu.lich_su_kho_id')
                ->leftJoin('nguyen_lieu', 'nguyen_lieu.id', '=', 'chi_tieu.nguyen_lieu_id')
                ->selectRaw("SUM(CASE WHEN phuong_thuc_thanh_toan = 'tiền mặt' THEN {$totalExpression} ELSE 0 END) as tong_tien_mat")
                ->selectRaw("SUM(CASE WHEN phuong_thuc_thanh_toan = 'chuyển khoản' THEN {$totalExpression} ELSE 0 END) as tong_tien_chuyen_khoan")
                ->selectRaw("SUM({$totalExpression}) as tong_chi")
                ->first();

            $summary = [
                'tong_tien_mat' => (float) ($summaryRow->tong_tien_mat ?? 0),
                'tong_tien_chuyen_khoan' => (float) ($summaryRow->tong_tien_chuyen_khoan ?? 0),
                'tong_chi' => (float) ($summaryRow->tong_chi ?? 0),
            ];
        }

        return view('manager.expenses.index', [
            'shiftGroups' => $shiftGroups,
            'selectedShift' => $selectedShift,
            'selectedShiftId' => $selectedShiftId,
            'filterDate' => $filterDate,
            'ingredients' => NguyenLieu::orderBy('ten_nguyen_lieu')->get(),
            'expenses' => $expenses,
            'summary' => $summary,
        ]);
    }

    public function create(Request $request)
    {
        $shiftGroups = $this->shiftGroups();
        $selectedShiftId = request('ca_lam_viec_id');
        
        return view('manager.expenses.create', [
            'shiftGroups' => $shiftGroups,
            'selectedShiftId' => $selectedShiftId,
            'ingredients' => NguyenLieu::orderBy('ten_nguyen_lieu')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ca_lam_viec_id' => 'required|exists:ca_lam_viec,id',
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'so_luong' => 'required|numeric|min:0.01',
            'don_gia' => 'required|numeric|min:0.01',
            'phuong_thuc_thanh_toan' => 'required|string|max:50',
            'ghi_chu' => 'nullable|string|max:500',
        ]);

        $method = $this->normalizePaymentMethod($request->input('phuong_thuc_thanh_toan'));
        if (! $method) {
            throw ValidationException::withMessages([
                'phuong_thuc_thanh_toan' => 'Phương thức thanh toán không hợp lệ.',
            ]);
        }

        $shift = CaLamViec::find((int) $request->input('ca_lam_viec_id'));
        if ($shift && $shift->daChot()) {
            return back()->withInput()->with('error', 'Ca đã chốt không thể thêm chi tiêu');
        }

        DB::transaction(function () use ($request, $method): void {
            $note = $request->filled('ghi_chu') ? trim((string) $request->input('ghi_chu')) : null;
            $nguyenLieuId = (int) $request->input('nguyen_lieu_id');
            $unitPrice = (float) $request->input('don_gia');

            $history = LichSuKho::create([
                'nguyen_lieu_id' => $nguyenLieuId,
                'loai_giao_dich' => 'nhập kho',
                'so_luong' => (float) $request->input('so_luong'),
                'gia_nhap' => $unitPrice,
                'ghi_chu' => $note,
                'nguoi_tao_id' => Auth::id(),
                'created_at' => now(),
            ]);

            ChiTieu::create([
                'ca_lam_viec_id' => (int) $request->input('ca_lam_viec_id'),
                'nguoi_tao_id' => Auth::id(),
                'nguyen_lieu_id' => $nguyenLieuId,
                'lich_su_kho_id' => $history->id,
                'phuong_thuc_thanh_toan' => $method,
                'ghi_chu' => $note,
            ]);
        });

        return redirect()
            ->route('manager.expenses.index', ['ca_lam_viec_id' => $request->input('ca_lam_viec_id')])
            ->with('success', 'Đã ghi nhận khoản chi.');
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
            $shiftDate = $shift->ngay_lam instanceof \Carbon\Carbon 
                ? $shift->ngay_lam->toDateString() 
                : (is_string($shift->ngay_lam) ? substr($shift->ngay_lam, 0, 10) : $shift->ngay_lam);

            return $shiftDate === $today &&
                   $shift->gio_bat_dau <= $currentTime &&
                   $shift->gio_ket_thuc >= $currentTime;
        });

        if ($activeShift) {
            return $activeShift;
        }


        return null;
    }

    // normalizePaymentMethod() => NormalizesPayment trait
}
