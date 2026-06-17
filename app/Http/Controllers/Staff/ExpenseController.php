<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreExpenseRequest;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\ChiTieu;
use App\Models\LichSuKho;
use App\Models\NguyenLieu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        // Current shift
        $currentShift = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', $today)
            ->where('gio_bat_dau', '<=', $currentTime)
            ->where('gio_ket_thuc', '>=', $currentTime)
            ->first();

        if (!$currentShift) {
            $currentShift = CaLamViec::where('nguoi_dung_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->orderBy('gio_bat_dau')
                ->first();
        }

        // All shifts for this user
        $shiftOptions = CaLamViec::where('nguoi_dung_id', $user->id)
            ->orderByDesc('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        // Mặc định lọc theo tuần hiện tại (thứ Hai → Chủ nhật). Bỏ lọc ngày bằng ?clear=1.
        $skipDefaultDate = $request->boolean('clear');
        $defaultFrom = $now->copy()->startOfWeek()->toDateString();
        $defaultTo = $now->copy()->endOfWeek()->toDateString();

        $filters = [
            'ca_lam_viec_id' => $request->query('ca_lam_viec_id'),
            'from_date' => $request->query('from_date', $skipDefaultDate ? null : $defaultFrom),
            'to_date' => $request->query('to_date', $skipDefaultDate ? null : $defaultTo),
        ];

        // Expenses (chỉ của tài khoản này)
        $expensesQuery = ChiTieu::query()
            ->with(['nguoiTao', 'nguyenLieu', 'lichSuKho'])
            ->where('chi_tieu.nguoi_tao_id', $user->id);

        $this->applyExpenseFilters($expensesQuery, $filters);

        $expenses = $expensesQuery
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        // Summary bám đúng bộ lọc đang áp dụng
        $totalExpression = 'COALESCE(lich_su_kho.so_luong, 0) * COALESCE(lich_su_kho.gia_nhap, 0)';

        $summaryQuery = ChiTieu::query()->where('chi_tieu.nguoi_tao_id', $user->id);
        $this->applyExpenseFilters($summaryQuery, $filters);

        $summaryRow = $summaryQuery
            ->leftJoin('lich_su_kho', 'lich_su_kho.id', '=', 'chi_tieu.lich_su_kho_id')
            ->leftJoin('nguyen_lieu', 'nguyen_lieu.id', '=', 'chi_tieu.nguyen_lieu_id')
            ->selectRaw("SUM(CASE WHEN chi_tieu.phuong_thuc_thanh_toan = 'tiền mặt' THEN {$totalExpression} ELSE 0 END) as tong_tien_mat")
            ->selectRaw("SUM(CASE WHEN chi_tieu.phuong_thuc_thanh_toan = 'chuyển khoản' THEN {$totalExpression} ELSE 0 END) as tong_tien_chuyen_khoan")
            ->selectRaw("SUM({$totalExpression}) as tong_chi")
            ->first();

        $summary = [
            'tong_tien_mat' => (float) ($summaryRow->tong_tien_mat ?? 0),
            'tong_tien_chuyen_khoan' => (float) ($summaryRow->tong_tien_chuyen_khoan ?? 0),
            'tong_chi' => (float) ($summaryRow->tong_chi ?? 0),
        ];

        return view('staff.expenses.index', compact('currentShift', 'expenses', 'summary', 'shiftOptions', 'filters'));
    }

    /**
     * Áp bộ lọc ca làm việc + khoảng ngày (theo ngày_lam của ca) cho query chi tiêu.
     */
    private function applyExpenseFilters(Builder $query, array $filters): void
    {
        // Chọn ca cụ thể thì ưu tiên ca đó, bỏ qua khoảng ngày (tránh ca khác tuần bị lọc mất).
        if (!empty($filters['ca_lam_viec_id'])) {
            $query->where('chi_tieu.ca_lam_viec_id', $filters['ca_lam_viec_id']);
            return;
        }

        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;

        if ($from || $to) {
            $query->whereHas('caLamViec', function (Builder $q) use ($from, $to) {
                if ($from) {
                    $q->whereDate('ngay_lam', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('ngay_lam', '<=', $to);
                }
            });
        }
    }

    public function create()
    {
        $user = Auth::user();
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $currentShift = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', $today)
            ->where('gio_bat_dau', '<=', $currentTime)
            ->where('gio_ket_thuc', '>=', $currentTime)
            ->first();

        if (!$currentShift) {
            $currentShift = CaLamViec::where('nguoi_dung_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->orderBy('gio_bat_dau')
                ->first();
        }

        $shiftOptions = CaLamViec::where('nguoi_dung_id', $user->id)
            ->orderByDesc('ngay_lam')
            ->orderBy('gio_bat_dau')
            ->get();

        $ingredients = NguyenLieu::orderBy('ten_nguyen_lieu')->get();

        return view('staff.expenses.create', compact('currentShift', 'shiftOptions', 'ingredients'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $request->validated(); // already validated by FormRequest

        $method = match (trim($request->phuong_thuc_thanh_toan)) {
            'tien_mat', 'tiền mặt' => 'tiền mặt',
            'chuyen_khoan', 'chuyển khoản' => 'chuyển khoản',
            default => null,
        };

        if (!$method) {
            throw ValidationException::withMessages([
                'phuong_thuc_thanh_toan' => 'Phương thức thanh toán không hợp lệ.',
            ]);
        }

        $shift = CaLamViec::find((int) $request->ca_lam_viec_id);
        if ($shift && $shift->daChot()) {
            return back()->withInput()->with('error', 'Ca đã chốt không thể thêm chi tiêu');
        }

        DB::transaction(function () use ($request, $method) {
            $note = $request->filled('ghi_chu') ? trim($request->ghi_chu) : null;
            $nguyenLieuId = (int) $request->nguyen_lieu_id;
            $unitPrice = (float) $request->don_gia;

            $history = LichSuKho::create([
                'nguyen_lieu_id' => $nguyenLieuId,
                'loai_giao_dich' => 'nhập kho',
                'so_luong' => (float) $request->so_luong,
                'gia_nhap' => $unitPrice,
                'ghi_chu' => $note,
                'nguoi_tao_id' => Auth::id(),
                'created_at' => now(),
            ]);

            ChiTieu::create([
                'ca_lam_viec_id' => (int) $request->ca_lam_viec_id,
                'nguoi_tao_id' => Auth::id(),
                'nguyen_lieu_id' => $nguyenLieuId,
                'lich_su_kho_id' => $history->id,
                'phuong_thuc_thanh_toan' => $method,
                'ghi_chu' => $note,
            ]);
        });

        return redirect()
            ->route('staff.expenses.index')
            ->with('success', 'Đã ghi nhận khoản chi.');
    }
}
