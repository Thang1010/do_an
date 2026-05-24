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
use App\Models\SanPhamKichCo;
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
    }

    private function syncProductSizes(SanPham $product, array $sizes, float $giaGoc, ?float $giaKhuyenMai, string $trangThaiBanDb): void
    {
        $keepKichCoIds = [];

        foreach ($sizes as $size) {
            $kichCoId = (string)($size['kich_co_id'] ?? '');
            $heSoGia = (float)($size['he_so_gia'] ?? 1);
            if ($heSoGia < 1) {
                $heSoGia = 1;
            }

            if ($kichCoId === 'khac') {
                $kichCo = KichCo::create([
                    'ma_kich_co' => trim((string)($size['ma_kich_co_moi'] ?? '')),
                    'ten_kich_co' => trim((string)($size['ten_kich_co_moi'] ?? '')),
                    'mo_ta' => trim((string)($size['mo_ta_kich_co_moi'] ?? '')) ?: null,
                ]);
            } else {
                $kichCo = KichCo::find((int)$kichCoId);
                if (!$kichCo) {
                    continue;
                }
            }

            $keepKichCoIds[] = $kichCo->id;

            SanPhamKichCo::updateOrCreate(
                [
                    'san_pham_id' => $product->id,
                    'kich_co_id'  => $kichCo->id,
                ],
                [
                    'gia_ban'        => round($giaGoc * $heSoGia, 2),
                    'gia_khuyen_mai' => $giaKhuyenMai !== null ? round($giaKhuyenMai * $heSoGia, 2) : null,
                    'trang_thai'     => $trangThaiBanDb,
                ]
            );
        }

        $query = SanPhamKichCo::where('san_pham_id', $product->id);
        if (!empty($keepKichCoIds)) {
            $query->whereNotIn('kich_co_id', $keepKichCoIds);
        }
        $query->delete();
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
            ->withCount(['chiTietDonHang as so_luong_ban' => function ($q) {
                $q->whereHas('donHang', fn($dh) => $dh->whereNotIn('trang_thai_don', ['huy', 'đã hủy']));
            }]);

        if ($request->filled('search')) {
            $query->where('ten_san_pham', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('danh_muc')) {
            $query->where('danh_muc_id', $request->danh_muc);
        }
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai_ban', $this->toDbTrangThaiBan($request->trang_thai));
        }

        $products  = $query->latest()->paginate(15)->withQueryString();
        $danhMucs  = DanhMuc::orderBy('ten_danh_muc')->get();

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
            'gia_khuyen_mai' => $validated['gia_khuyen_mai'] ?? null,
            'trang_thai_ban' => $validated['trang_thai_ban'],
        ];
        $data['trang_thai_ban'] = $this->toDbTrangThaiBan($data['trang_thai_ban']);
        $data['slug'] = Str::slug($request->ten_san_pham) . '-' . time();

        // Upload ảnh chính
        if ($request->hasFile('anh_chinh')) {
            $path = $request->file('anh_chinh')->store('products', 'public');
            $data['hinh_anh_chinh'] = $path;
        }

        $product = DB::transaction(function () use ($data, $validated) {
            $product = SanPham::create($data);
            $this->syncProductSizes(
                $product,
                $validated['sizes'] ?? [],
                (float) $data['gia_goc'],
                $data['gia_khuyen_mai'] === null ? null : (float) $data['gia_khuyen_mai'],
                $data['trang_thai_ban']
            );
            $this->syncProductRecipes($product, $validated['recipes'] ?? []);
            return $product;
        });

        return redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được thêm thành công.");
    }

    public function edit(int $id)
    {
        $product  = SanPham::with(['hinhAnhSanPham', 'sanPhamKichCo.kichCo', 'congThucSanPham.nguyenLieu'])->findOrFail($id);
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
            'gia_khuyen_mai' => $validated['gia_khuyen_mai'] ?? null,
            'trang_thai_ban' => $validated['trang_thai_ban'],
        ];
        $data['trang_thai_ban'] = $this->toDbTrangThaiBan($data['trang_thai_ban']);

        if ($request->hasFile('anh_chinh')) {
            if ($product->hinh_anh_chinh) {
                Storage::disk('public')->delete($product->hinh_anh_chinh);
            }
            $data['hinh_anh_chinh'] = $request->file('anh_chinh')->store('products', 'public');
        }

        DB::transaction(function () use ($product, $data, $validated) {
            $product->update($data);
            $this->syncProductSizes(
                $product,
                $validated['sizes'] ?? [],
                (float) $data['gia_goc'],
                $data['gia_khuyen_mai'] === null ? null : (float) $data['gia_khuyen_mai'],
                $data['trang_thai_ban']
            );
            $this->syncProductRecipes($product, $validated['recipes'] ?? []);
        });

        return redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được cập nhật.");
    }

    public function exportRecipesExcel()
    {
        $products = SanPham::with(['sanPhamKichCo.kichCo', 'congThucSanPham.nguyenLieu'])
            ->orderBy('ten_san_pham')
            ->get();

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
            $sizes = $product->sanPhamKichCo;
            $sizeList = $sizes->isEmpty()
                ? collect([null])
                : $sizes;

            $recipesByIngredient = $product->congThucSanPham->keyBy('nguyen_lieu_id');
            $firstRow = true;
            $startRow = $rowCursor;

            foreach ($sizeList as $size) {
                $sizeLabel = 'Mặc định';
                if ($size && $size->kichCo) {
                    $kichCo = $size->kichCo;
                    $baseLabel = trim((string) ($kichCo->ma_kich_co ?? ''));
                    if ($baseLabel === '') {
                        $baseLabel = trim((string) ($kichCo->ten_kich_co ?? ''));
                    }

                    if ($baseLabel !== '') {
                        $moTa = trim((string) ($kichCo->mo_ta ?? ''));
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
                    if (! $recipe) {
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
            Storage::disk('public')->delete($product->hinh_anh_chinh);
        }
        $product->hinhAnhSanPham()->each(function ($img) {
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
        $product->update([
            'trang_thai_ban' => $this->toDbTrangThaiBan($request->trang_thai),
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
            'hinh_anh'   => 'required|mimes:jpg,jpeg,png,gif,webp,avif,bmp,tiff,svg|max:5120',
            'la_anh_chinh' => 'nullable|boolean',
        ]);

        $product = SanPham::findOrFail($id);
        $path    = $request->file('hinh_anh')->store('products', 'public');

        HinhAnhSanPham::create([
            'san_pham_id' => $product->id,
            'duong_dan_anh' => $path,
            'la_anh_chinh'  => $request->boolean('la_anh_chinh'),
        ]);

        return redirect()->route('manager.products.index')->with('success', 'Đã thêm ảnh sản phẩm.');
    }

    /** Xóa ảnh sản phẩm */
    public function destroyImage(int $id, int $imageId)
    {
        $image = HinhAnhSanPham::where('san_pham_id', $id)->findOrFail($imageId);
        Storage::disk('public')->delete($image->duong_dan_anh);
        $image->delete();
        return redirect()->route('manager.products.index')->with('success', 'Đã xóa ảnh.');
    }
}
