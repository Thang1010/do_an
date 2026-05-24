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
use PhpOffice\PhpSpreadsheet\IOFactory;
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

        $balanceExpression = $this->stockBalanceExpression('lich_su_kho');
        $inventory = $this->buildInventoryQuery($request)
            ->orderByRaw("CASE WHEN COALESCE({$balanceExpression}, 0) <= 0 THEN 0 ELSE 1 END")
            ->orderBy('nguyen_lieu.ten_nguyen_lieu')
            ->paginate(20)
            ->withQueryString();

        $lowCount = DB::query()
            ->fromSub($this->applyPurposeFilter($this->baseInventoryQuery(), $currentPurpose), 'inventory_balance')
            ->where('so_luong', '<=', 0)
            ->count();

        $importLog = $this->buildHistoryQuery($request, 'import')
            ->latest('created_at')
            ->paginate(15, ['*'], 'import_page')
            ->withQueryString();

        $exportLog = $this->buildHistoryQuery($request, 'export')
            ->latest('created_at')
            ->paginate(15, ['*'], 'export_page')
            ->withQueryString();

        $nguyenLieus = NguyenLieu::orderBy('ten_nguyen_lieu')->get();

        $managerNames = LichSuKho::query()
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'lich_su_kho.nguoi_tao_id')
            ->whereNotNull('nguoi_dung.ho_ten')
            ->distinct()
            ->orderBy('nguoi_dung.ho_ten')
            ->pluck('nguoi_dung.ho_ten');

        return view('manager.inventory.index', compact(
            'inventory',
            'lowCount',
            'importLog',
            'exportLog',
            'nguyenLieus',
            'managerNames',
            'purposeTabs',
            'currentPurpose',
            'purposeLabel'
        ));
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
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'so_luong'       => 'required|numeric|min:0.01',
            'don_gia'        => 'nullable|numeric|min:0',
            'ghi_chu'        => 'nullable|string|max:500',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ], [
            'nguyen_lieu_id.required' => 'Vui lòng chọn nguyên liệu.',
            'so_luong.required'       => 'Vui lòng nhập số lượng.',
            'so_luong.min'            => 'Số lượng phải lớn hơn 0.',
        ]);

        $nguyenLieu = NguyenLieu::findOrFail($request->nguyen_lieu_id);
        $shiftId = $this->resolveExpenseShiftId();
        if (! $shiftId) {
            return back()
                ->withInput()
                ->with('error', 'Không tìm thấy ca làm việc để ghi nhận chi tiêu. Vui lòng tạo ca trước khi nhập kho.');
        }

        DB::transaction(function () use ($nguyenLieu, $request, $shiftId): void {
            $unitPrice = $request->filled('don_gia') ? (float) $request->don_gia : null;

            $history = LichSuKho::create([
                'nguyen_lieu_id'   => $nguyenLieu->id,
                'loai_giao_dich'   => 'nhập kho',
                'tham_chieu_loai'  => 'chi_tieu',
                'so_luong'         => $request->so_luong,
                'gia_nhap'         => $unitPrice,
                'nguoi_tao_id'     => Auth::id(),
                'ghi_chu'          => $request->ghi_chu,
                'created_at'       => now(),
            ]);

            $expense = ChiTieu::create([
                'ca_lam_viec_id' => $shiftId,
                'nguoi_tao_id' => Auth::id(),
                'nguyen_lieu_id' => $nguyenLieu->id,
                'lich_su_kho_id' => $history->id,
                'phuong_thuc_thanh_toan' => 'tiền mặt',
                'ghi_chu' => $request->filled('ghi_chu') ? trim((string) $request->ghi_chu) : null,
            ]);

            $history->update([
                'tham_chieu_id' => $expense->id,
            ]);
        });

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã nhập {$request->so_luong} {$nguyenLieu->don_vi_tinh} «{$nguyenLieu->ten_nguyen_lieu}».");
    }

    public function storeImportExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ], [
            'excel_file.required' => 'Vui lòng chọn file Excel để nhập kho.',
            'excel_file.mimes' => 'File không hợp lệ. Chỉ chấp nhận xlsx, xls hoặc csv.',
            'excel_file.max' => 'Kích thước file tối đa là 5MB.',
        ]);

        $file = $request->file('excel_file');
        $shiftId = $this->resolveExpenseShiftId();
        if (! $shiftId) {
            return redirect()
                ->route('manager.inventory.import')
                ->with('error', 'Không tìm thấy ca làm việc để ghi nhận chi tiêu. Vui lòng tạo ca trước khi nhập kho.');
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable $exception) {
            return redirect()
                ->route('manager.inventory.import')
                ->with('error', 'Không thể đọc file Excel. Vui lòng kiểm tra lại định dạng dữ liệu.');
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $ingredientCell = trim((string) ($row[0] ?? ''));
            $quantityCell = trim((string) ($row[1] ?? ''));
            $unitPriceCell = trim((string) ($row[2] ?? ''));
            $note = trim((string) ($row[3] ?? ''));

            if ($line === 1 && $this->isHeaderRow([$ingredientCell, $quantityCell, $unitPriceCell])) {
                continue;
            }

            if ($ingredientCell === '' && $quantityCell === '' && $unitPriceCell === '' && $note === '') {
                continue;
            }

            $quantity = $this->parseDecimalValue($quantityCell);
            if ($quantity === null || $quantity <= 0) {
                $failedCount++;
                $errors[] = "Dòng {$line}: Số lượng không hợp lệ.";
                continue;
            }

            $unitPrice = null;
            if ($unitPriceCell !== '') {
                $unitPrice = $this->parseDecimalValue($unitPriceCell);
                if ($unitPrice === null || $unitPrice < 0) {
                    $failedCount++;
                    $errors[] = "Dòng {$line}: Đơn giá không hợp lệ.";
                    continue;
                }
            }

            $nguyenLieu = $this->resolveNguyenLieu($ingredientCell);
            if (!$nguyenLieu) {
                $failedCount++;
                $errors[] = "Dòng {$line}: Không tìm thấy nguyên liệu '{$ingredientCell}'.";
                continue;
            }

            DB::transaction(function () use ($nguyenLieu, $quantity, $unitPrice, $note, $shiftId) {
                $history = LichSuKho::create([
                    'nguyen_lieu_id' => $nguyenLieu->id,
                    'loai_giao_dich' => 'nhập kho',
                    'tham_chieu_loai' => 'chi_tieu',
                    'so_luong' => $quantity,
                    'gia_nhap' => $unitPrice,
                    'nguoi_tao_id' => Auth::id(),
                    'ghi_chu' => $note !== '' ? $note : 'Nhập kho bằng file Excel',
                    'created_at' => now(),
                ]);

                $expense = ChiTieu::create([
                    'ca_lam_viec_id' => $shiftId,
                    'nguoi_tao_id' => Auth::id(),
                    'nguyen_lieu_id' => $nguyenLieu->id,
                    'lich_su_kho_id' => $history->id,
                    'phuong_thuc_thanh_toan' => 'tiền mặt',
                    'ghi_chu' => $note !== '' ? $note : 'Nhập kho bằng file Excel',
                ]);

                $history->update([
                    'tham_chieu_id' => $expense->id,
                ]);
            });

            $successCount++;
        }

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }
        $redirect = redirect()->route('manager.inventory.index', $redirectParams);

        if ($successCount === 0) {
            $sampleErrors = implode(' ', array_slice($errors, 0, 3));
            return $redirect->with('error', 'Không có dòng nào hợp lệ để nhập kho. ' . $sampleErrors);
        }

        $successMessage = "Đã nhập kho thành công {$successCount} dòng từ file.";
        if ($failedCount > 0) {
            $sampleErrors = implode(' ', array_slice($errors, 0, 3));
            return $redirect
                ->with('success', $successMessage)
                ->with('warning', "Có {$failedCount} dòng bị bỏ qua. {$sampleErrors}");
        }

        return $redirect->with('success', $successMessage);
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
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'so_luong'       => 'required|numeric|min:0.01',
            'ly_do'          => 'nullable|string|max:500',
            'return_muc_dich_su_dung' => 'nullable|string|max:120',
        ]);

        $nguyenLieu = NguyenLieu::findOrFail($request->nguyen_lieu_id);
        $currentStock = $this->currentStock((int) $nguyenLieu->id);

        if ((float) $request->so_luong > $currentStock) {
            return back()->withErrors(['so_luong' => 'Số lượng xuất vượt tồn kho hiện tại.'])
                ->withInput();
        }

        DB::transaction(function () use ($nguyenLieu, $request): void {
            LichSuKho::create([
                'nguyen_lieu_id'   => $nguyenLieu->id,
                'loai_giao_dich'   => 'xuất kho',
                'so_luong'         => $request->so_luong,
                'nguoi_tao_id'     => Auth::id(),
                'ghi_chu'          => $request->ly_do,
                'created_at'       => now(),
            ]);
        });

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã xuất {$request->so_luong} {$nguyenLieu->don_vi_tinh} «{$nguyenLieu->ten_nguyen_lieu}».");
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
                : 'Cập nhật tồn kho theo mục đích sử dụng',
            'created_at' => now(),
        ]);

        $returnPurpose = $this->normalizePurposeFilter($request->input('return_muc_dich_su_dung'));
        $redirectParams = ['tab' => 'stock'];
        if ($returnPurpose !== null) {
            $redirectParams['muc_dich_su_dung'] = $returnPurpose;
        }

        return redirect()->route('manager.inventory.index', $redirectParams)
            ->with('success', "Đã cập nhật tồn kho «{$nguyenLieu->ten_nguyen_lieu}».");
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
            if ($request->trang_thai === 'low') {
                $query->having('so_luong', '<=', 0);
            } elseif ($request->trang_thai === 'ok') {
                $query->having('so_luong', '>', 0);
            }
        }

        return $query;
    }

    private function baseInventoryQuery(): Builder
    {
        $balanceExpression = $this->stockBalanceExpression();

        return NguyenLieu::query()
            ->leftJoin('lich_su_kho', 'lich_su_kho.nguyen_lieu_id', '=', 'nguyen_lieu.id')
            ->select(
                'nguyen_lieu.id',
                'nguyen_lieu.ten_nguyen_lieu',
                'nguyen_lieu.don_vi_tinh',
                'nguyen_lieu.muc_dich_su_dung',
                'nguyen_lieu.created_at'
            )
            ->selectRaw("COALESCE({$balanceExpression}, 0) as so_luong")
            ->groupBy(
                'nguyen_lieu.id',
                'nguyen_lieu.ten_nguyen_lieu',
                'nguyen_lieu.don_vi_tinh',
                'nguyen_lieu.muc_dich_su_dung',
                'nguyen_lieu.created_at'
            );
    }

    private function buildHistoryQuery(Request $request, string $type): Builder
    {
        $query = LichSuKho::with('nguyenLieu', 'nguoiTao')
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
                $subQuery->where('ho_ten', 'like', '%' . $managerName . '%');
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

    private function resolveNguyenLieu(string $identifier): ?NguyenLieu
    {
        if ($identifier === '') {
            return null;
        }

        if (is_numeric($identifier)) {
            return NguyenLieu::find((int) $identifier);
        }

        $normalized = mb_strtolower(trim($identifier));
        return NguyenLieu::whereRaw('LOWER(ten_nguyen_lieu) = ?', [$normalized])->first()
            ?? NguyenLieu::where('ten_nguyen_lieu', 'like', trim($identifier))->first();
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

    private function parseDecimalValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = str_replace(["\xc2\xa0", ' '], '', $text);

        if (str_contains($text, ',') && str_contains($text, '.')) {
            if (strrpos($text, ',') > strrpos($text, '.')) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif (str_contains($text, ',')) {
            $text = str_replace(',', '.', $text);
        }

        if (!is_numeric($text)) {
            return null;
        }

        return (float) $text;
    }

    private function isHeaderRow(array $columns): bool
    {
        $ingredient = mb_strtolower(trim((string) ($columns[0] ?? '')));
        $quantity = mb_strtolower(trim((string) ($columns[1] ?? '')));

        return str_contains($ingredient, 'nguyên')
            || str_contains($ingredient, 'nguyen')
            || str_contains($ingredient, 'tên')
            || str_contains($quantity, 'số lượng')
            || str_contains($quantity, 'so luong');
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
                'Tham chiếu',
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
                    $this->formatReference($log->tham_chieu_loai, $log->tham_chieu_id),
                    $log->nguoiTao->ho_ten ?? '—',
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
            'Tham chiếu',
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
                $this->formatReference($log->tham_chieu_loai, $log->tham_chieu_id),
                $log->nguoiTao->ho_ten ?? '—',
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

    private function formatReference(?string $type, ?int $id): string
    {
        if (!$type || !$id) {
            return '—';
        }

        if ($type === 'don_hang') {
            return 'Đơn hàng #' . $id;
        }

        if ($type === 'phieu_nhap') {
            return 'Phiếu nhập #' . $id;
        }

        return strtoupper($type) . ' #' . $id;
    }
}
