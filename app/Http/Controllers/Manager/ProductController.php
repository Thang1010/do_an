<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreProductRequest;
use App\Models\SanPham;
use App\Models\DanhMuc;
use App\Models\CongThucSanPham;
use App\Models\KichCo;
use App\Models\NguyenLieu;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductController extends Controller
{
    // validateProductRequest() => Đã chuyển sang App\Http\Requests\Manager\StoreProductRequest

    private function syncProductRecipes(SanPham $product, array $recipes): void
    {
        $cleaned = collect($recipes)
            ->map(function ($recipe) {
                return [
                    'nguyen_lieu_id' => isset($recipe['nguyen_lieu_id']) ? (int) $recipe['nguyen_lieu_id'] : 0,
                    'so_luong_can' => isset($recipe['so_luong_can']) ? (float) $recipe['so_luong_can'] : 0,
                ];
            })
            ->filter(function (array $recipe) {
                return $recipe['nguyen_lieu_id'] > 0 && $recipe['so_luong_can'] > 0;
            })
            ->unique('nguyen_lieu_id')
            ->values();

        CongThucSanPham::where('san_pham_id', $product->id)->delete();

        foreach ($cleaned as $recipe) {
            CongThucSanPham::create([
                'san_pham_id' => $product->id,
                'nguyen_lieu_id' => $recipe['nguyen_lieu_id'],
                'so_luong_can' => $recipe['so_luong_can'],
                'created_at' => now(),
            ]);
        }

        if ($product->loai_quan_ly_kho === 'theo nguyên liệu' && rtrim($product->trang_thai_ban) === 'đang bán') {
            $ingredientIds = $cleaned->pluck('nguyen_lieu_id')->all();
            if (empty($ingredientIds)) {
                // Có công thức nhưng chưa điền công thức → tự động ngừng bán
                $product->update(['trang_thai_ban' => 'ngừng bán']);
            } else {
                $balanceExpression = \App\Enums\TransactionType::stockBalanceExpression('lich_su_kho');
                $stocks = DB::table('lich_su_kho')
                    ->whereIn('nguyen_lieu_id', $ingredientIds)
                    ->select('nguyen_lieu_id', DB::raw("COALESCE({$balanceExpression}, 0) as so_luong"))
                    ->groupBy('nguyen_lieu_id')
                    ->pluck('so_luong', 'nguyen_lieu_id');

                foreach ($cleaned as $recipe) {
                    $stock = (float) ($stocks[$recipe['nguyen_lieu_id']] ?? 0);
                    if ($stock < (float) $recipe['so_luong_can']) {
                        // Tồn kho không đủ để làm 1 phần → tự động ngừng bán
                        $product->update(['trang_thai_ban' => 'ngừng bán']);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Đồng bộ "nguyên liệu tự thân" cho sản phẩm KHÔNG công thức.
     *
     * - Không công thức: sản phẩm CHÍNH LÀ một nguyên liệu trong kho để theo dõi
     *   số lượng (tạo mới hoặc kích hoạt lại nếu trước đó đã ẩn).
     * - Có công thức: sản phẩm dùng các nguyên liệu khác → ẩn (ngừng sử dụng)
     *   nguyên liệu tự thân để khỏi lừa tồn kho, nhưng KHÔNG xóa cứng nhằm giữ
     *   dữ liệu nhập/xuất (kế toán) cũ.
     */
    private function syncSelfIngredient(SanPham $product, bool $hasRecipe): void
    {
        $selfIngredient = NguyenLieu::where('san_pham_id', $product->id)->first();

        if ($hasRecipe) {
            if ($selfIngredient && $selfIngredient->trang_thai !== NguyenLieu::TRANG_THAI_NGUNG_DUNG) {
                $selfIngredient->update(['trang_thai' => NguyenLieu::TRANG_THAI_NGUNG_DUNG]);
            }
            return;
        }

        $name = mb_substr(trim((string) $product->ten_san_pham), 0, 40);

        if ($selfIngredient) {
            $selfIngredient->update([
                'ten_nguyen_lieu' => $name,
                'trang_thai' => NguyenLieu::TRANG_THAI_DANG_DUNG,
            ]);
            return;
        }

        NguyenLieu::create([
            'san_pham_id' => $product->id,
            'ten_nguyen_lieu' => $name,
            'don_vi_tinh' => 'chai',
            'muc_dich_su_dung' => 'Sản phẩm bán lẻ',
            'trang_thai' => NguyenLieu::TRANG_THAI_DANG_DUNG,
            'created_at' => now(),
        ]);
    }

    private function syncProductSizes(SanPham $product, array $sizes): void
    {
        $syncData = [];

        foreach ($sizes as $size) {
            $kichCoId = (string) ($size['kich_co_id'] ?? '');
            $heSoGia = (float) ($size['he_so_gia'] ?? 1);

            if ($kichCoId === 'khac') {
                $tenKichCo = trim((string) ($size['ten_kich_co_moi'] ?? ''));
                $maKichCo = trim((string) ($size['ma_kich_co_moi'] ?? ''));
                $moTa = trim((string) ($size['mo_ta_kich_co_moi'] ?? ''));

                if (empty($tenKichCo)) continue;

                $kc = KichCo::where('ten_kich_co', $tenKichCo);
                if (!empty($maKichCo)) {
                    $kc = $kc->orWhere('ma_kich_co', $maKichCo);
                }
                $kc = $kc->first();

                if (!$kc) {
                    $kc = KichCo::create([
                        'ma_kich_co' => $maKichCo ?: null,
                        'ten_kich_co' => $tenKichCo,
                        'mo_ta' => $moTa ?: null,
                        'he_so_gia' => $heSoGia > 0 ? $heSoGia : 1,
                    ]);
                } else {
                    // Trùng mã/tên → cập nhật (sửa) kích cỡ đã có theo giá trị mới, gồm cả hệ số giá.
                    $kc->update([
                        'ten_kich_co' => $tenKichCo,
                        'ma_kich_co'  => $maKichCo ?: $kc->ma_kich_co,
                        'mo_ta'       => $moTa !== '' ? $moTa : null,
                        'he_so_gia'   => $heSoGia > 0 ? $heSoGia : 1,
                    ]);
                }
                
                $syncData[] = $kc->id;
            } else {
                $kcId = (int) $kichCoId;
                $kc = KichCo::find($kcId);
                if ($kc) {
                    // Cho phép sửa hệ số giá ngay trên size có sẵn → cập nhật khi giá trị thay đổi.
                    if ($heSoGia > 0 && abs((float) $kc->he_so_gia - $heSoGia) > 0.0001) {
                        $kc->update(['he_so_gia' => $heSoGia]);
                    }
                    $syncData[] = $kc->id;
                }
            }
        }

        $product->kichCo()->sync($syncData);
    }

    private function toDbTrangThaiBan(string $status): string
    {
        return in_array($status, ['dang_ban', 'đang bán'], true) ? 'đang bán' : 'ngừng bán';
    }

    private function toFormTrangThaiBan(?string $status): string
    {
        return in_array($status, ['dang_ban', 'đang bán'], true) ? 'dang_ban' : 'ngung_ban';
    }

    /**
     * Kiểm tra điều kiện để bật bán ("đang bán").
     * Trả về null nếu đủ điều kiện; ngược lại trả về chuỗi lý do cụ thể.
     *
     * - Sản phẩm quản lý "theo số lượng" (không dùng công thức): luôn đủ điều kiện (miễn trừ).
     * - Sản phẩm quản lý "theo nguyên liệu": phải có công thức và tồn kho mỗi nguyên liệu
     *   phải ≥ số lượng cần cho 1 phần.
     */
    private function sellBlockReason(SanPham $product): ?string
    {
        if (rtrim($product->loai_quan_ly_kho ?? '') !== 'theo nguyên liệu') {
            return null;
        }

        $recipes = CongThucSanPham::with('nguyenLieu')
            ->where('san_pham_id', $product->id)
            ->get();

        if ($recipes->isEmpty()) {
            return 'Không thể bật bán: sản phẩm chưa có công thức. Vui lòng thêm công thức (nguyên liệu) trước.';
        }

        $balanceExpression = \App\Enums\TransactionType::stockBalanceExpression('lich_su_kho');
        $stocks = DB::table('lich_su_kho')
            ->whereIn('nguyen_lieu_id', $recipes->pluck('nguyen_lieu_id'))
            ->select('nguyen_lieu_id', DB::raw("COALESCE({$balanceExpression}, 0) as so_luong"))
            ->groupBy('nguyen_lieu_id')
            ->pluck('so_luong', 'nguyen_lieu_id');

        $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');

        $insufficient = [];
        foreach ($recipes as $recipe) {
            $need = (float) $recipe->so_luong_can;
            $have = (float) ($stocks[$recipe->nguyen_lieu_id] ?? 0);
            if ($have < $need) {
                $name = $recipe->nguyenLieu?->ten_nguyen_lieu ?? ('#' . $recipe->nguyen_lieu_id);
                $unit = $recipe->nguyenLieu?->don_vi_tinh ? ' ' . $recipe->nguyenLieu->don_vi_tinh : '';
                $insufficient[] = "{$name} (cần {$fmt($need)}{$unit}, còn {$fmt($have)}{$unit})";
            }
        }

        if (!empty($insufficient)) {
            return 'Không thể bật bán vì nguyên liệu không đủ để làm: ' . implode('; ', $insufficient) . '.';
        }

        return null;
    }

    /**
     * Lưu ảnh sản phẩm lên S3 (S3 lưu được mọi định dạng ảnh).
     * - Định dạng GD nén được (jpg/png/gif/webp/…): resize 1200px + nén JPG 80% để nhẹ.
     * - Định dạng GD không xử lý được (svg/tiff/heic/…): lưu nguyên file gốc, Laravel S3
     *   tự gán Content-Type theo đuôi file để trình duyệt hiển thị đúng.
     * - Nếu cả hai đều thất bại → ném ValidationException (thông báo thân thiện, không 500).
     */
    private function storeProductImage(\Illuminate\Http\UploadedFile $file): string
    {
        // 1) Thử nén bằng GD
        try {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->decode($file)->scaleDown(width: 1200);
            $filename = 'products/' . Str::uuid() . '-' . time() . '.jpg';
            Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
            return $filename;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('GD không nén được ảnh, lưu nguyên file gốc lên S3.', [
                'ext' => $file->getClientOriginalExtension(),
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Lưu nguyên file gốc (putFileAs tự nhận diện & gán Content-Type theo file)
        try {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'img');
            $name = Str::uuid() . '-' . time() . '.' . $ext;
            $path = Storage::disk('s3')->putFileAs('products', $file, $name);
            if (!$path) {
                throw new \RuntimeException('putFileAs trả về false.');
            }
            return $path;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tải ảnh sản phẩm lên S3 thất bại.', [
                'error' => $e->getMessage(),
            ]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'anh_chinh' => 'Không thể tải ảnh lên. Vui lòng thử lại.',
            ]);
        }
    }

    public function index(Request $request)
    {
        $query = SanPham::with(['danhMuc'])
            ->withCount(['chiTietDonHang as so_luong_ban']);

        if ($request->filled('search')) {
            $query->where('ten_san_pham', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('danh_muc')) {
            $query->where('danh_muc_id', $request->danh_muc);
        }
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai_ban', $this->toDbTrangThaiBan($request->trang_thai));
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $danhMucs = DanhMuc::orderBy('ten_danh_muc')->get();

        return view('manager.products.index', compact('products', 'danhMucs'));
    }

    public function create()
    {
        $danhMucs = DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get();
        $kichCos = KichCo::orderBy('he_so_gia')->orderBy('ten_kich_co')->get();
        $nguyenLieus = NguyenLieu::query()->dangSuDung()->orderBy('ten_nguyen_lieu')->get();

        return view('manager.products.create', compact('danhMucs', 'kichCos', 'nguyenLieus'));
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $data = [
            'ten_san_pham' => $validated['ten_san_pham'],
            'danh_muc_id' => $validated['danh_muc_id'],
            'mo_ta' => $validated['mo_ta'] ?? null,
            'gia_goc' => $validated['gia_goc'],
            'gia_khuyen_mai' => $validated['gia_khuyen_mai'] ?? $validated['gia_goc'],
            'trang_thai_ban' => $validated['trang_thai_ban'],
            'nhiet_do' => isset($validated['nhiet_do']) ? implode(',', (array) $validated['nhiet_do']) : null,
        ];
        $data['trang_thai_ban'] = $this->toDbTrangThaiBan($data['trang_thai_ban']);
        $coCongThuc = $request->boolean('co_cong_thuc');
        $data['loai_quan_ly_kho'] = $coCongThuc ? 'theo nguyên liệu' : 'theo số lượng';
        $data['slug'] = Str::slug($request->ten_san_pham) . '-' . time();

        // Upload ảnh chính (nén nếu được, fallback lưu file gốc — không để lỗi 500)
        if ($request->hasFile('anh_chinh')) {
            $data['hinh_anh'] = $this->storeProductImage($request->file('anh_chinh'));
        }

        $intendedStatus = $data['trang_thai_ban'];

        $product = DB::transaction(function () use ($data, $validated, $coCongThuc) {
            $product = SanPham::create($data);
            $this->syncProductSizes($product, $coCongThuc ? ($validated['sizes'] ?? []) : []);
            $this->syncProductRecipes($product, $coCongThuc ? ($validated['recipes'] ?? []) : []);
            $this->syncSelfIngredient($product, $coCongThuc);
            return $product;
        });

        $redirect = redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được thêm thành công.");

        if ($intendedStatus === 'đang bán' && $product->trang_thai_ban === 'ngừng bán') {
            $reason = $this->sellBlockReason($product->fresh());
            $redirect->with('warning', $reason ?? 'Sản phẩm được chuyển sang "Ngừng bán" vì chưa đủ điều kiện bán.');
        }

        return $redirect;
    }

    public function edit(int $id)
    {
        $product = SanPham::with(['congThucSanPham.nguyenLieu', 'kichCo'])->findOrFail($id);
        $product->trang_thai_ban = $this->toFormTrangThaiBan($product->trang_thai_ban);
        $danhMucs = DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get();
        $kichCos = KichCo::orderBy('he_so_gia')->orderBy('ten_kich_co')->get();
        $nguyenLieus = NguyenLieu::query()->dangSuDung()->orderBy('ten_nguyen_lieu')->get();

        return view('manager.products.create', compact('product', 'danhMucs', 'kichCos', 'nguyenLieus'));
    }

    public function update(StoreProductRequest $request, int $id)
    {
        $product = SanPham::findOrFail($id);
        $validated = $request->validated();

        $data = [
            'ten_san_pham' => $validated['ten_san_pham'],
            'danh_muc_id' => $validated['danh_muc_id'],
            'mo_ta' => $validated['mo_ta'] ?? null,
            'gia_goc' => $validated['gia_goc'],
            'gia_khuyen_mai' => $validated['gia_khuyen_mai'] ?? $validated['gia_goc'],
            'trang_thai_ban' => $validated['trang_thai_ban'],
            'nhiet_do' => isset($validated['nhiet_do']) ? implode(',', (array) $validated['nhiet_do']) : null,
        ];
        $data['trang_thai_ban'] = $this->toDbTrangThaiBan($data['trang_thai_ban']);
        $coCongThuc = $request->boolean('co_cong_thuc');
        $data['loai_quan_ly_kho'] = $coCongThuc ? 'theo nguyên liệu' : 'theo số lượng';

        if ($request->hasFile('anh_chinh')) {
            // Tải ảnh mới trước; chỉ xoá ảnh cũ khi tải thành công.
            $newImage = $this->storeProductImage($request->file('anh_chinh'));
            if ($product->hinh_anh) {
                Storage::disk('s3')->delete($product->hinh_anh);
                Storage::disk('public')->delete($product->hinh_anh); // Fallback for old local files
            }
            $data['hinh_anh'] = $newImage;
        }

        $intendedStatus = $data['trang_thai_ban'];

        DB::transaction(function () use ($product, $data, $validated, $coCongThuc) {
            $product->update($data);
            $this->syncProductSizes($product, $coCongThuc ? ($validated['sizes'] ?? []) : []);
            $this->syncProductRecipes($product, $coCongThuc ? ($validated['recipes'] ?? []) : []);
            $this->syncSelfIngredient($product, $coCongThuc);
        });

        $redirect = redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được cập nhật.");

        if ($intendedStatus === 'đang bán' && $product->fresh()->trang_thai_ban === 'ngừng bán') {
            $reason = $this->sellBlockReason($product->fresh());
            $redirect->with('warning', $reason ?? 'Sản phẩm đã tự động chuyển sang "Ngừng bán" vì chưa đủ điều kiện bán.');
        }

        return $redirect;
    }

    public function exportRecipesExcel()
    {
        $products = SanPham::with(['congThucSanPham.nguyenLieu', 'kichCo'])
            ->orderBy('ten_san_pham')
            ->get();

        $allSizes = KichCo::orderBy('he_so_gia')->orderBy('ten_kich_co')->get();

        $ingredientIds = CongThucSanPham::query()
            ->select('nguyen_lieu_id')
            ->distinct()
            ->pluck('nguyen_lieu_id');

        $ingredients = NguyenLieu::query()
            ->when($ingredientIds->isNotEmpty(), function ($query) use ($ingredientIds) {
                $query->whereIn('id', $ingredientIds);
            })
            ->orderBy('ten_nguyen_lieu')
            ->get();

        $headers = [
            'STT',
            'Tên sản phẩm',
            'Ghi chú / Mô tả',
            'Size',
        ];

        foreach ($ingredients as $ingredient) {
            $headers[] = $ingredient->ten_nguyen_lieu . ' (' . $ingredient->don_vi_tinh . ')';
        }

        $rows = [];
        $index = 1;
        $mergeRanges = [];
        $rowCursor = 0;

        foreach ($products as $product) {
            $sizeList = $allSizes->isEmpty()
                ? collect([null])
                : $allSizes;

            $recipesByIngredient = $product->congThucSanPham->keyBy('nguyen_lieu_id');
            $firstRow = true;
            $startRow = $rowCursor;

            foreach ($sizeList as $size) {
                $sizeLabel = 'Mặc định';
                if ($size) {
                    $baseLabel = trim((string) ($size->ma_kich_co ?? ''));
                    if ($baseLabel === '') {
                        $baseLabel = trim((string) ($size->ten_kich_co ?? ''));
                    }

                    if ($baseLabel !== '') {
                        $moTa = trim((string) ($size->mo_ta ?? ''));
                        $sizeLabel = $moTa !== '' ? ($baseLabel . '(' . $moTa . ')') : $baseLabel;
                    }
                }

                $row = [
                    $firstRow ? $index : '',
                    $firstRow ? $product->ten_san_pham : '',
                    $firstRow ? (string) ($product->mo_ta ?? '') : '',
                    $sizeLabel,
                ];

                $heSoGia = $size ? (float) ($size->he_so_gia ?? 1) : 1;

                foreach ($ingredients as $ingredient) {
                    $recipe = $recipesByIngredient->get($ingredient->id);
                    if (!$recipe) {
                        $row[] = '';
                        continue;
                    }

                    $qty = (float) $recipe->so_luong_can * $heSoGia;
                    $qtyText = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
                    $row[] = $qtyText !== '' ? ($qtyText . ' ' . $ingredient->don_vi_tinh) : '';
                }

                $rows[] = $row;
                $firstRow = false;
                $rowCursor++;
            }

            $rowCount = $sizeList->count();
            if ($rowCount > 1) {
                $mergeRanges[] = [
                    'start' => $startRow,
                    'count' => $rowCount,
                ];
            }

            $index++;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headers, null, 'A1');
        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A2');
        }

        foreach ($mergeRanges as $range) {
            $startRowNumber = $range['start'] + 2;
            $endRowNumber = $startRowNumber + $range['count'] - 1;
            $sheet->mergeCells("A{$startRowNumber}:A{$endRowNumber}");
            $sheet->mergeCells("B{$startRowNumber}:B{$endRowNumber}");
            $sheet->mergeCells("C{$startRowNumber}:C{$endRowNumber}");
        }

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'recipes_excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $filename = 'cong-thuc-san-pham-' . now()->format('Ymd-His') . '.xlsx';

        return response()->download(
            $tempFile,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function destroy(int $id)
    {
        $product = SanPham::findOrFail($id);

        // Xóa ảnh
        if ($product->hinh_anh) {
            Storage::disk('s3')->delete($product->hinh_anh);
            Storage::disk('public')->delete($product->hinh_anh);
        }

        // Ẩn nguyên liệu tự thân (nếu có) để khỏi hiển thị trong kho, vẫn giữ dữ liệu cũ.
        NguyenLieu::where('san_pham_id', $product->id)
            ->update(['trang_thai' => NguyenLieu::TRANG_THAI_NGUNG_DUNG]);

        $product->delete();

        return redirect()->route('manager.products.index')->with('success', "Đã xóa sản phẩm «{$product->ten_san_pham}».");
    }

    /** Cập nhật trạng thái bán (AJAX toggle) */
    public function updateStatus(Request $request, int $id)
    {
        $product = SanPham::findOrFail($id);
        $newStatus = $this->toDbTrangThaiBan($request->trang_thai);

        if ($newStatus === 'đang bán') {
            $reason = $this->sellBlockReason($product);
            if ($reason !== null) {
                return response()->json([
                    'success' => false,
                    'message' => $reason,
                ], 422);
            }
        }

        $product->update([
            'trang_thai_ban' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'trang_thai' => $this->toFormTrangThaiBan($product->trang_thai_ban),
        ]);
    }

}
