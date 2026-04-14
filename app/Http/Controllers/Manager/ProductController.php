<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\DanhMuc;
use App\Models\HinhAnhSanPham;
use App\Models\KichCo;
use App\Models\SanPhamKichCo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    private function validateProductRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'ten_san_pham'   => 'required|string|max:200',
            'danh_muc_id'    => 'required|exists:danh_muc,id',
            'gia_goc'        => 'required|numeric|min:0',
            'gia_khuyen_mai' => 'nullable|numeric|min:0',
            'trang_thai_ban' => 'required|in:dang_ban,ngung_ban',
            'anh_chinh'      => 'nullable|mimes:jpg,jpeg,png,gif,webp,avif,bmp,tiff,svg|max:5120',
            'sizes'          => 'nullable|array',
            'sizes.*.kich_co_id' => 'required',
            'sizes.*.he_so_gia'  => 'required|numeric|min:1',
            'sizes.*.ma_kich_co_moi'  => 'nullable|string|max:20',
            'sizes.*.ten_kich_co_moi' => 'nullable|string|max:50',
            'sizes.*.mo_ta_kich_co_moi' => 'nullable|string|max:500',
        ], [
            'ten_san_pham.required' => 'Vui lòng nhập tên sản phẩm.',
            'danh_muc_id.required'  => 'Vui lòng chọn danh mục.',
            'gia_goc.required'      => 'Vui lòng nhập giá sản phẩm.',
            'sizes.*.kich_co_id.required' => 'Vui lòng chọn kích cỡ.',
            'sizes.*.he_so_gia.required'  => 'Vui lòng nhập hệ số giá.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('sizes', []) as $index => $size) {
                $kichCoId = (string)($size['kich_co_id'] ?? '');

                if ($kichCoId === 'khac') {
                    $tenMoi = trim((string)($size['ten_kich_co_moi'] ?? ''));
                    $maMoi  = trim((string)($size['ma_kich_co_moi'] ?? ''));

                    if ($tenMoi === '') {
                        $validator->errors()->add("sizes.$index.ten_kich_co_moi", 'Vui lòng nhập tên kích cỡ mới.');
                    } elseif (KichCo::where('ten_kich_co', $tenMoi)->exists()) {
                        $validator->errors()->add("sizes.$index.ten_kich_co_moi", 'Tên kích cỡ này đã tồn tại, vui lòng chọn trong danh sách.');
                    }

                    if ($maMoi === '') {
                        $validator->errors()->add("sizes.$index.ma_kich_co_moi", 'Vui lòng nhập mã kích cỡ mới.');
                    } elseif (KichCo::where('ma_kich_co', $maMoi)->exists()) {
                        $validator->errors()->add("sizes.$index.ma_kich_co_moi", 'Mã kích cỡ này đã tồn tại.');
                    }
                } else {
                    $selectedId = (int)$kichCoId;
                    if ($selectedId <= 0 || !KichCo::whereKey($selectedId)->exists()) {
                        $validator->errors()->add("sizes.$index.kich_co_id", 'Kích cỡ không hợp lệ.');
                    }
                }
            }
        });

        return $validator->validate();
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
        return view('manager.products.create', compact('danhMucs', 'kichCos'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProductRequest($request);

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
            return $product;
        });

        return redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được thêm thành công.");
    }

    public function edit(int $id)
    {
        $product  = SanPham::with(['hinhAnhSanPham', 'sanPhamKichCo.kichCo'])->findOrFail($id);
        $product->trang_thai_ban = $this->toFormTrangThaiBan($product->trang_thai_ban);
        $danhMucs = DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get();
        $kichCos = KichCo::orderBy('ten_kich_co')->get();
        return view('manager.products.create', compact('product', 'danhMucs', 'kichCos'));
    }

    public function update(Request $request, int $id)
    {
        $product = SanPham::findOrFail($id);
        $validated = $this->validateProductRequest($request);

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
        });

        return redirect()->route('manager.products.index')
            ->with('success', "Sản phẩm «{$product->ten_san_pham}» đã được cập nhật.");
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

        return back()->with('success', "Đã xóa sản phẩm «{$product->ten_san_pham}».");
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

        return back()->with('success', 'Đã thêm ảnh sản phẩm.');
    }

    /** Xóa ảnh sản phẩm */
    public function destroyImage(int $id, int $imageId)
    {
        $image = HinhAnhSanPham::where('san_pham_id', $id)->findOrFail($imageId);
        Storage::disk('public')->delete($image->duong_dan_anh);
        $image->delete();
        return back()->with('success', 'Đã xóa ảnh.');
    }
}
