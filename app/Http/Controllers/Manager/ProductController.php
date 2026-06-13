<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreProductRequest;
use App\Models\SanPham;
use App\Models\DanhMuc;
use App\Models\HinhAnhSanPham;
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
            if (!empty($ingredientIds)) {
                $balanceExpression = \App\Enums\TransactionType::stockBalanceExpression('lich_su_kho');
                $stocks = DB::table('lich_su_kho')
                    ->whereIn('nguyen_lieu_id', $ingredientIds)
                    ->select('nguyen_lieu_id', DB::raw("COALESCE({$balanceExpression}, 0) as so_luong"))
                    ->groupBy('nguyen_lieu_id')
                    ->pluck('so_luong', 'nguyen_lieu_id');

                foreach ($ingredientIds as $id) {
                    $stock = (float) ($stocks[$id] ?? 0);
                    if ($stock <= 0) {
                        $product->update(['trang_thai_ban' => 'ngừng bán']);
                        break;
                    }
                }
            }
        }
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
                    $updateData = [];
                    if ($maKichCo && !$kc->ma_kich_co) $updateData['ma_kich_co'] = $maKichCo;
                    if ($moTa && !$kc->mo_ta) $updateData['mo_ta'] = $moTa;
                    // Only update he_so_gia if it was explicitly provided and valid
                    if ($heSoGia > 0 && $kc->he_so_gia == 1.0) $updateData['he_so_gia'] = $heSoGia;
                    if (!empty($updateData)) $kc->update($updateData);
                }
                
                $syncData[] = $kc->id;
            } else {
                $kcId = (int) $kichCoId;
                if ($kcId > 0 && KichCo::find($kcId)) {
                    $syncData[] = $kcId;
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

    public function index(Request $request)
    {
        $query = SanPham::with(['danhMuc', 'hinhAnhSanPham'])
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
        $kichCos = KichCo::orderBy('ten_kich_co')->get();
        $nguyenLieus = NguyenLieu::orderBy('ten_nguyen_lieu')->get();

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
        $data['slug'] = Str::slug($request->ten_san_pham) . '-' . time();

        // Upload ảnh chính
        if ($request->hasFile('anh_chinh')) {
            $file = $request->file('anh_chinh');
            
            // Nén ảnh: Resize max width 1200px, quality 80%
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->decode($file);
            $image->scaleDown(width: 1200);
            
            // Lưu lên S3
            $filename = 'products/' . Str::uuid() . '-' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
            
            $data['hinh_anh_chinh'] = $filename;
        }

        $product = DB::transaction(function () use ($data, $validated) {
            $product = SanPham::create($data);
            $this->syncProductSizes(
                $product,
                $validated['sizes'] ?? []
            );
            $this->syncProductRecipes($product, $validated['recipes'] ?? []);
            return $product;
        });

        return redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được thêm thành công.");
    }

    public function edit(int $id)
    {
        $product = SanPham::with(['hinhAnhSanPham', 'congThucSanPham.nguyenLieu', 'kichCo'])->findOrFail($id);
        $product->trang_thai_ban = $this->toFormTrangThaiBan($product->trang_thai_ban);
        $danhMucs = DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get();
        $kichCos = KichCo::orderBy('ten_kich_co')->get();
        $nguyenLieus = NguyenLieu::orderBy('ten_nguyen_lieu')->get();

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

        if ($request->hasFile('anh_chinh')) {
            if ($product->hinh_anh_chinh) {
                Storage::disk('s3')->delete($product->hinh_anh_chinh);
                Storage::disk('public')->delete($product->hinh_anh_chinh); // Fallback for old local files
            }
            $file = $request->file('anh_chinh');
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->decode($file)->scaleDown(width: 1200);
            
            $filename = 'products/' . Str::uuid() . '-' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
            $data['hinh_anh_chinh'] = $filename;
        }

        DB::transaction(function () use ($product, $data, $validated) {
            $product->update($data);
            $this->syncProductSizes(
                $product,
                $validated['sizes'] ?? []
            );
            $this->syncProductRecipes($product, $validated['recipes'] ?? []);
        });

        return redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được cập nhật.");
    }

    public function exportRecipesExcel()
    {
        $products = SanPham::with(['congThucSanPham.nguyenLieu', 'kichCo'])
            ->orderBy('ten_san_pham')
            ->get();

        $allSizes = KichCo::whereNull('san_pham_id')->orderBy('ten_kich_co')->get();

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
                    $sizeLabel,
                ];

                foreach ($ingredients as $ingredient) {
                    $recipe = $recipesByIngredient->get($ingredient->id);
                    if (!$recipe) {
                        $row[] = '';
                        continue;
                    }

                    $qty = (float) $recipe->so_luong_can;
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
        if ($product->hinh_anh_chinh) {
            Storage::disk('s3')->delete($product->hinh_anh_chinh);
            Storage::disk('public')->delete($product->hinh_anh_chinh);
        }
        $product->hinhAnhSanPham()->each(function ($img) {
            Storage::disk('s3')->delete($img->duong_dan_anh);
            Storage::disk('public')->delete($img->duong_dan_anh);
            $img->delete();
        });

        $product->delete();

        return redirect()->route('manager.products.index')->with('success', "Đã xóa sản phẩm «{$product->ten_san_pham}».");
    }

    /** Cập nhật trạng thái bán (AJAX toggle) */
    public function updateStatus(Request $request, int $id)
    {
        $product = SanPham::findOrFail($id);
        $newStatus = $this->toDbTrangThaiBan($request->trang_thai);

        if ($newStatus === 'đang bán' && $product->loai_quan_ly_kho === 'theo nguyên liệu') {
            $ingredientIds = CongThucSanPham::where('san_pham_id', $product->id)->pluck('nguyen_lieu_id');
            if ($ingredientIds->isNotEmpty()) {
                $balanceExpression = \App\Enums\TransactionType::stockBalanceExpression('lich_su_kho');
                $stocks = DB::table('lich_su_kho')
                    ->whereIn('nguyen_lieu_id', $ingredientIds)
                    ->select('nguyen_lieu_id', DB::raw("COALESCE({$balanceExpression}, 0) as so_luong"))
                    ->groupBy('nguyen_lieu_id')
                    ->pluck('so_luong', 'nguyen_lieu_id');

                foreach ($ingredientIds as $idIng) {
                    $stock = (float) ($stocks[$idIng] ?? 0);
                    if ($stock <= 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Không thể bật bán vì có nguyên liệu đã hết hàng.',
                        ], 422);
                    }
                }
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

    /** Quản lý hình ảnh sản phẩm */
    public function images(int $id)
    {
        $product = SanPham::with('hinhAnhSanPham')->findOrFail($id);
        return view('manager.products.images', compact('product'));
    }

    /** Upload thêm ảnh */
    public function storeImage(Request $request, int $id)
    {
        $request->validate([
            'hinh_anh' => 'required|mimes:jpg,jpeg,png,gif,webp,avif,bmp,tiff,svg|max:5120',
            'la_anh_chinh' => 'nullable|boolean',
        ]);

        $product = SanPham::findOrFail($id);
        
        $file = $request->file('hinh_anh');
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $image = $manager->decode($file)->scaleDown(width: 1200);
        
        $filename = 'products/' . Str::uuid() . '-' . time() . '.' . $file->getClientOriginalExtension();
        Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));

        HinhAnhSanPham::create([
            'san_pham_id' => $product->id,
            'duong_dan_anh' => $filename,
            'la_anh_chinh' => $request->boolean('la_anh_chinh'),
        ]);

        return redirect()->route('manager.products.index')->with('success', 'Đã thêm ảnh sản phẩm.');
    }

    /** Xóa ảnh sản phẩm */
    public function destroyImage(int $id, int $imageId)
    {
        $image = HinhAnhSanPham::where('san_pham_id', $id)->findOrFail($imageId);
        Storage::disk('s3')->delete($image->duong_dan_anh);
        Storage::disk('public')->delete($image->duong_dan_anh);
        $image->delete();
        return redirect()->route('manager.products.index')->with('success', 'Đã xóa ảnh.');
    }
}
