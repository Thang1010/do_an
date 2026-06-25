<?php

namespace App\Http\Controllers\Manager;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\CaLamViec;
use App\Models\ChiTieu;
use App\Models\NguyenLieu;
use App\Models\LichSuKho;
use App\Traits\CalculatesStock;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InventoryController extends Controller
{
    use CalculatesStock;
    public function index(Request $request)
    {
        $today = now()->toDateString();
        if (! $request->filled('import_from_date') && ! $request->filled('import_to_date')) {
            $request->merge([
                'import_from_date' => $today,
                'import_to_date' => $today,
            ]);
        }

        if (! $request->filled('export_from_date') && ! $request->filled('export_to_date')) {
            $request->merge([
                'export_from_date' => $today,
                'export_to_date' => $today,
            ]);
        }

        $currentPurpose = $this->normalizePurposeFilter($request->input('muc_dich_su_dung'));
        $purposeTabs = $this->purposeTabs();
        $purposeLabel = $this->resolvePurposeLabel($currentPurpose);

        $inventory = $this->buildInventoryQuery($request)
            ->orderByRaw("CASE 
                WHEN {$this->cupsExpression()} <= 0 THEN 0 
                WHEN {$this->cupsExpression()} <= 3 THEN 1 
                ELSE 2 
            END")
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->paginate(10)
            ->withQueryString();

        // "Sắp hết" = với tồn kho hiện tại chỉ làm được <= 3 cốc/sản phẩm (và > 0).
        $lowCount = DB::query()
            ->fromSub($this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose), 'inventory_balance')
            ->whereRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) <= 3')
            ->whereRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) > 0')
            ->count();
        
        $hetCount = DB::query()
            ->fromSub($this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose), 'inventory_balance')
            ->whereRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) <= 0')
            ->count();

        $importLog = $this->buildHistoryQuery($request, 'import')
            ->latest('created_at')
            ->paginate(10, ['*'], 'import_page')
            ->withQueryString();

        $exportLog = $this->buildHistoryQuery($request, 'export')
            ->latest('created_at')
            ->paginate(10, ['*'], 'export_page')
            ->withQueryString();

        $nguyenLieus = NguyenLieu::query()->dangSuDung()->orderBy('ten_nguyen_lieu')->get();

        $managerNames = LichSuKho::query()
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'lich_su_kho.nguoi_tao_id')
            ->whereNotNull('nguoi_dung.email')
            ->distinct()
            ->orderBy('nguoi_dung.email')
            ->pluck('nguoi_dung.email');

        return view('manager.inventory.index', compact(
            'inventory',
            'lowCount',
            'hetCount',
            'importLog',
            'exportLog',
            'nguyenLieus',
            'managerNames',
            'purposeTabs',
            'currentPurpose',
            'purposeLabel'
        ));
    }

    /**
     * Polling nhẹ: chỉ tính lại số nguyên liệu hết/sắp hết và trả về dải cảnh báo.
     */
    public function alertPoll(Request $request)
    {
        $currentPurpose = $this->normalizePurposeFilter($request->input('muc_dich_su_dung'));

        $lowCount = DB::query()
            ->fromSub($this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose), 'inventory_balance')
            ->whereRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) <= 3')
            ->whereRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) > 0')
            ->count();

        $hetCount = DB::query()
            ->fromSub($this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose), 'inventory_balance')
            ->whereRaw('FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1)) <= 0')
            ->count();

        return response()->json([
            'html' => view('manager.inventory.partials.stock-alert', compact('hetCount', 'lowCount'))->render(),
        ]);
    }

    public function import(Request $request)
    {
        $currentPurpose = $this->normalizePurposeFilter($request->input('muc_dich_su_dung'));
        $nguyenLieus = $this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose)
            ->orderByRaw('CASE WHEN so_luong <= 0 THEN 0 ELSE 1 END')
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->get();

        $selectedNguyenLieuId = $request->query('nguyen_lieu_id');
        $purposeLabel = $this->resolvePurposeLabel($currentPurpose);

        return view('manager.inventory.import', compact(
            'nguyenLieus',
            'selectedNguyenLieuId',
            'currentPurpose',
            'purposeLabel'
        ));
    }

    public function storeImport(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'items.*.so_luong'       => 'required|numeric|min:0.01',
            'items.*.don_gia'        => 'required|numeric|min:0',
            'items.*.ghi_chu'        => 'nullable|string|max:500',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ], [
            'items.*.nguyen_lieu_id.required' => 'Vui lòng chọn nguyên liệu.',
            'items.*.so_luong.required'       => 'Vui lòng nhập số lượng.',
            'items.*.so_luong.min'            => 'Số lượng phải lớn hơn 0.',
            'items.*.don_gia.required'        => 'Vui lòng nhập giá nhập/SP.',
            'items.*.don_gia.min'             => 'Giá nhập không được âm.',
        ]);

        $shiftId = $this->resolveExpenseShiftId();
        if (! $shiftId) {
            return back()
                ->withInput()
                ->with('error', 'Không tìm thấy ca làm việc để ghi nhận chi tiêu. Vui lòng tạo ca trước khi nhập kho.');
        }

        $shift = CaLamViec::find($shiftId);
        if ($shift && $shift->daChot()) {
            return back()->withInput()->with('error', 'Ca đã chốt không thể thêm chi tiêu');
        }

        $totalItems = 0;

        DB::transaction(function () use ($request, $shiftId, &$totalItems): void {
            foreach ($request->items as $index => $itemData) {
                $nguyenLieu = NguyenLieu::find($itemData['nguyen_lieu_id']);
                if (!$nguyenLieu) continue;

                $unitPrice = isset($itemData['don_gia']) && $itemData['don_gia'] !== '' ? (float) $itemData['don_gia'] : null;
                $ghiChu = $itemData['ghi_chu'] ?? null;

                $history = LichSuKho::create([
                    'nguyen_lieu_id'   => $nguyenLieu->id,
                    'loai_giao_dich'   => 'nhập kho',
                    'so_luong'         => (float) $itemData['so_luong'],
                    'gia_nhap'         => $unitPrice,
                    'nguoi_tao_id'     => Auth::id(),
                    'ghi_chu'          => $ghiChu,
                    'created_at'       => now(),
                ]);

                ChiTieu::create([
                    'ca_lam_viec_id' => $shiftId,
                    'nguoi_tao_id' => Auth::id(),
                    'nguyen_lieu_id' => $nguyenLieu->id,
                    'lich_su_kho_id' => $history->id,
                    'phuong_thuc_thanh_toan' => 'tiền mặt',
                    'ghi_chu' => $ghiChu !== '' ? $ghiChu : null,
                ]);

                $totalItems++;
            }
        });

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã nhập {$totalItems} loại nguyên liệu thành công.");
    }



    public function export(Request $request)
    {
        $currentPurpose = $this->normalizePurposeFilter($request->input('muc_dich_su_dung'));
        $nguyenLieus = $this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose)
            ->orderByRaw('CASE WHEN so_luong <= 0 THEN 0 ELSE 1 END')
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->get();
        $selectedNguyenLieuId = $request->query('nguyen_lieu_id');
        $purposeLabel = $this->resolvePurposeLabel($currentPurpose);

        return view('manager.inventory.export', compact(
            'nguyenLieus',
            'selectedNguyenLieuId',
            'currentPurpose',
            'purposeLabel'
        ));
    }

    public function storeExport(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'items.*.so_luong' => 'required|numeric|min:0.01',
            'items.*.ly_do' => 'nullable|string|max:500',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ]);

        $errors = [];
        $totalItems = 0;

        DB::transaction(function () use ($request, &$errors, &$totalItems): void {
            foreach ($request->items as $index => $itemData) {
                $nguyenLieu = NguyenLieu::find($itemData['nguyen_lieu_id']);
                if (!$nguyenLieu) continue;

                $currentStock = $this->currentStock((int) $nguyenLieu->id);
                $soLuong = (float) $itemData['so_luong'];

                if ($soLuong > $currentStock) {
                    $errors[] = "Số lượng xuất của {$nguyenLieu->ten_nguyen_lieu} vượt tồn kho hiện tại.";
                    continue;
                }

                LichSuKho::create([
                    'nguyen_lieu_id'   => $nguyenLieu->id,
                    'loai_giao_dich'   => 'xuất kho',
                    'so_luong'         => $soLuong,
                    'nguoi_tao_id'     => Auth::id(),
                    'ghi_chu'          => $itemData['ly_do'] ?? null,
                    'created_at'       => now(),
                ]);
                
                $totalItems++;
            }
        });

        if (count($errors) > 0) {
            return back()->withErrors($errors)->withInput();
        }

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã xuất {$totalItems} loại nguyên liệu thành công.");
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'chenh_lech' => 'required|numeric|not_in:0',
            'ly_do_kiem_ke' => 'nullable|string|max:500',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ], [
            'chenh_lech.not_in' => 'Chênh lệch kiểm kê phải khác 0.',
        ]);

        $nguyenLieu = NguyenLieu::findOrFail($request->nguyen_lieu_id);
        $delta = (float) $request->chenh_lech;

        LichSuKho::create([
            'nguyen_lieu_id' => $nguyenLieu->id,
            'loai_giao_dich' => 'điều chỉnh',
            'so_luong' => $delta,
            'nguoi_tao_id' => Auth::id(),
            'ghi_chu' => $request->filled('ly_do_kiem_ke')
                ? trim((string) $request->ly_do_kiem_ke)
                : 'Điều chỉnh kiểm kê tồn kho',
            'created_at' => now(),
        ]);

        $label = $delta > 0 ? 'tăng' : 'giảm';
        $absDelta = number_format(abs($delta), 2, ',', '.');

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã ghi nhận điều chỉnh {$label} {$absDelta} {$nguyenLieu->don_vi_tinh} cho «{$nguyenLieu->ten_nguyen_lieu}».");
    }

    public function updateStock(Request $request)
    {
        $request->validate([
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'so_luong_moi' => 'required|numeric|min:0',
            'ghi_chu' => 'nullable|string|max:500',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ]);

        $nguyenLieu = NguyenLieu::findOrFail($request->nguyen_lieu_id);
        $currentStock = $this->currentStock((int) $nguyenLieu->id);
        $newStock = (float) $request->so_luong_moi;
        $delta = $newStock - $currentStock;

        if (abs($delta) < 0.00001) {
            return back()->with('info', 'Số lượng tồn kho không thay đổi.');
        }

        LichSuKho::create([
            'nguyen_lieu_id' => $nguyenLieu->id,
            'loai_giao_dich' => 'điều chỉnh',
            'so_luong' => $delta,
            'nguoi_tao_id' => Auth::id(),
            'ghi_chu' => $request->filled('ghi_chu')
                ? trim((string) $request->ghi_chu)
                : 'Điều chỉnh tồn kho (kiểm kê)',
            'created_at' => now(),
        ]);

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã điều chỉnh tồn kho «{$nguyenLieu->ten_nguyen_lieu}».");
    }

    public function exportStockExcel(Request $request)
    {
        $inventory = $this->buildInventoryQuery($request)
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->get();

        $headers = [
            'STT',
            'Nguyên liệu',
            'Đơn vị tính',
            'Mục đích sử dụng',
            'Tồn kho hiện tại',
            'Trạng thái',
        ];

        $rows = [];
        foreach ($inventory as $index => $item) {
            $status = 'Đủ hàng';
            if ((float) $item->so_luong <= 0.0) {
                $status = 'Hết hàng';
            }

            $rows[] = [
                $index + 1,
                $item->ten_nguyen_lieu,
                $item->don_vi_tinh,
                $item->muc_dich_su_dung ?? '—',
                (float) $item->so_luong,
                $status,
            ];
        }

        $filename = 'ton-kho-hien-tai-' . now()->format('Ymd-His') . '.xlsx';
        return $this->downloadExcel($headers, $rows, $filename);
    }

    public function exportImportHistoryExcel(Request $request)
    {
        return $this->exportHistoryExcel($request, 'import');
    }

    public function exportExportHistoryExcel(Request $request)
    {
        return $this->exportHistoryExcel($request, 'export');
    }

    private function buildInventoryQuery(Request $request): Builder
    {
        $query = $this->baseInventoryQuery();

        $currentPurpose = $this->normalizePurposeFilter($request->input('muc_dich_su_dung'));
        $this->applyPurposeFilter($query, $currentPurpose);

        if ($request->filled('search')) {
            $query->where('nguyen_lieu.ten_nguyen_lieu', 'like', '%' . trim((string) $request->search) . '%');
        }

        if ($request->filled('trang_thai')) {
            $expr = 'FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1))';
            if ($request->trang_thai === 'het') {
                $query->havingRaw("{$expr} <= 0");
            } elseif ($request->trang_thai === 'sap_het') {
                $query->havingRaw("{$expr} > 0 AND {$expr} <= 3");
            } elseif ($request->trang_thai === 'ok') {
                $query->havingRaw("{$expr} > 3");
            }
        }

        return $query;
    }

    private function baseInventoryQuery(): Builder
    {
        $balanceExpression = $this->stockBalanceExpression();

        return NguyenLieu::query()
            ->dangSuDung()
            ->leftJoin('lich_su_kho', 'lich_su_kho.nguyen_lieu_id', '=', 'nguyen_lieu.id')
            ->select(
                'nguyen_lieu.id',
                'nguyen_lieu.ten_nguyen_lieu',
                'nguyen_lieu.don_vi_tinh',
                'nguyen_lieu.muc_dich_su_dung',
                'nguyen_lieu.created_at'
            )
            ->selectRaw("COALESCE({$balanceExpression}, 0) as so_luong")
            // Mức tiêu hao lớn nhất của 1 sản phẩm dùng nguyên liệu này (để tính "làm
            // được bao nhiêu cốc"). NULL nếu không sản phẩm nào dùng (vd nguyên liệu
            // tự thân của sản phẩm bán lẻ) → coi như 1 đơn vị / sản phẩm.
            ->selectRaw('(SELECT MAX(ctsp.so_luong_can) FROM cong_thuc_san_pham ctsp WHERE ctsp.nguyen_lieu_id = nguyen_lieu.id) as max_tieu_hao')
            ->groupBy(
                'nguyen_lieu.id',
                'nguyen_lieu.ten_nguyen_lieu',
                'nguyen_lieu.don_vi_tinh',
                'nguyen_lieu.muc_dich_su_dung',
                'nguyen_lieu.created_at'
            );
    }

    /** Biểu thức SQL: số cốc/sản phẩm có thể làm với tồn kho hiện tại. */
    private function cupsExpression(): string
    {
        return 'FLOOR(so_luong / GREATEST(COALESCE(max_tieu_hao, 1), 1))';
    }

    private function buildHistoryQuery(Request $request, string $type): Builder
    {
        $query = LichSuKho::with('nguyenLieu', 'nguoiTao', 'chiTieu')
            ->whereIn('loai_giao_dich', $this->transactionTypeValues($type));

        $currentPurpose = $this->normalizePurposeFilter($request->input('muc_dich_su_dung'));
        if ($currentPurpose !== null) {
            $query->whereHas('nguyenLieu', function (Builder $subQuery) use ($currentPurpose) {
                $this->applyPurposeFilter($subQuery, $currentPurpose, 'nguyen_lieu');
            });
        }

        $prefix = $type === 'import' ? 'import' : 'export';
        $fromDate = $this->parseDateBoundary($request->input("{$prefix}_from_date"), false);
        $toDate = $this->parseDateBoundary($request->input("{$prefix}_to_date"), true);
        $managerName = trim((string) $request->input("{$prefix}_manager"));

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        if ($managerName !== '') {
            $query->whereHas('nguoiTao', function (Builder $subQuery) use ($managerName) {
                $subQuery->where('email', 'like', '%' . $managerName . '%');
            });
        }

        return $query;
    }

    private function transactionTypeValues(string $type): array
    {
        return TransactionType::transactionTypeValues($type);
    }

    // stockBalanceExpression() và currentStock()
    // => Đã chuyển sang CalculatesStock trait

    private function parseDateBoundary(?string $date, bool $isEndOfDay): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);
            return $isEndOfDay ? $parsed->endOfDay() : $parsed->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function resolveExpenseShiftId(): ?int
    {
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $activeShiftId = CaLamViec::query()
            ->whereDate('ngay_lam', $today)
            ->where('gio_bat_dau', '<=', $currentTime)
            ->where('gio_ket_thuc', '>=', $currentTime)
            ->orderBy('gio_bat_dau')
            ->value('id');

        if ($activeShiftId) {
            return (int) $activeShiftId;
        }

        $todayShiftId = CaLamViec::query()
            ->whereDate('ngay_lam', $today)
            ->orderByDesc('gio_bat_dau')
            ->value('id');

        if ($todayShiftId) {
            return (int) $todayShiftId;
        }

        $latestShiftId = CaLamViec::query()
            ->orderByDesc('ngay_lam')
            ->orderByDesc('gio_bat_dau')
            ->value('id');

        return $latestShiftId ? (int) $latestShiftId : null;
    }

    private function normalizePurposeFilter(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function resolvePurposeLabel(?string $purpose): string
    {
        if ($purpose === null || $purpose === '') {
            return 'Tất cả';
        }

        if ($purpose === '__none__') {
            return 'Chưa phân loại';
        }

        return $purpose;
    }

    private function purposeTabs(): array
    {
        $counts = NguyenLieu::query()
            ->selectRaw("COALESCE(muc_dich_su_dung, '__none__') as muc_dich, COUNT(*) as total")
            ->groupBy('muc_dich')
            ->pluck('total', 'muc_dich')
            ->all();

        $options = NguyenLieu::query()
            ->whereNotNull('muc_dich_su_dung')
            ->where('muc_dich_su_dung', '!=', '')
            ->orderBy('muc_dich_su_dung')
            ->pluck('muc_dich_su_dung')
            ->unique()
            ->values()
            ->all();

        $tabs = [
            '' => [
                'label' => 'Tất cả',
                'count' => array_sum($counts),
            ],
        ];

        foreach ($options as $option) {
            $tabs[$option] = [
                'label' => $option,
                'count' => $counts[$option] ?? 0,
            ];
        }

        if (isset($counts['__none__'])) {
            $tabs['__none__'] = [
                'label' => 'Chưa phân loại',
                'count' => $counts['__none__'],
            ];
        }

        return $tabs;
    }

    private function applyPurposeFilter(Builder $query, ?string $purpose, string $table = 'nguyen_lieu'): Builder
    {
        if ($purpose === null || $purpose === '') {
            return $query;
        }

        $column = $table . '.muc_dich_su_dung';
        return $query->where(function (Builder $builder) use ($column, $purpose) {
            if ($purpose === '__none__') {
                $builder->whereNull($column)->orWhere($column, '=', '');
                return;
            }

            $builder->where($column, $purpose);
        });
    }

    private function exportHistoryExcel(Request $request, string $type)
    {
        $logs = $this->buildHistoryQuery($request, $type)
            ->latest('created_at')
            ->get();

        if ($type === 'import') {
            $headers = [
                'STT',
                'Thời gian',
                'Nguyên liệu',
                'Số lượng nhập',
                'Đơn vị',
                'Giá nhập',
                'Tổng tiền',
                'Người nhập',
                'Ghi chú',
            ];

            $rows = [];
            foreach ($logs as $index => $log) {
                $unitPrice = $log->gia_nhap !== null
                    ? (float) $log->gia_nhap
                    : null;
                $quantity = (float) $log->so_luong;
                $rows[] = [
                    $index + 1,
                    $this->formatDateTime($log->created_at),
                    $log->nguyenLieu->ten_nguyen_lieu ?? '—',
                    $quantity,
                    $log->nguyenLieu->don_vi_tinh ?? '',
                    $unitPrice,
                    $unitPrice !== null ? $unitPrice * $quantity : null,
                    $log->nguoiTao->ho_ten ?? $log->nguoiTao->email ?? '—',
                    $log->ghi_chu,
                ];
            }

            $filename = 'lich-su-nhap-kho-' . now()->format('Ymd-His') . '.xlsx';
            return $this->downloadExcel($headers, $rows, $filename);
        }

        $headers = [
            'STT',
            'Thời gian',
            'Nguyên liệu',
            'Loại giao dịch',
            'Số lượng biến động',
            'Đơn vị',
            'Người thao tác',
            'Lý do / Ghi chú',
        ];

        $rows = [];
        foreach ($logs as $index => $log) {
            $rows[] = [
                $index + 1,
                $this->formatDateTime($log->created_at),
                $log->nguyenLieu->ten_nguyen_lieu ?? '—',
                $log->loai_giao_dich,
                (float) $log->so_luong,
                $log->nguyenLieu->don_vi_tinh ?? '',
                $log->nguoiTao->ho_ten ?? $log->nguoiTao->email ?? '—',
                $log->ghi_chu,
            ];
        }

        $filename = 'lich-su-xuat-kho-' . now()->format('Ymd-His') . '.xlsx';
        return $this->downloadExcel($headers, $rows, $filename);
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

        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_excel_');
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

    private function formatDateTime(mixed $value): string
    {
        if (!$value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $exception) {
            return '—';
        }
    }

}
