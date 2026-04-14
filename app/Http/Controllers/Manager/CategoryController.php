<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    private function toDbTrangThai(?string $status): string
    {
        return in_array($status, ['ngung_dung', 'ngưng dùng'], true)
            ? 'ngưng dùng'
            : 'đang dùng';
    }

    private function makeUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        if ($base === '') {
            $base = 'danh-muc';
        }

        $slug = $base;
        $i = 1;

        while (
            DanhMuc::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function index(Request $request)
    {
        $query = DanhMuc::query()->withCount('sanPham');

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('ten_danh_muc', 'like', "%{$s}%")
                  ->orWhere('slug', 'like', "%{$s}%");
            });
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $this->toDbTrangThai($request->trang_thai));
        }

        $categories = $query->latest()->paginate(15)->withQueryString();

        return view('manager.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_danh_muc' => 'required|string|max:150|unique:danh_muc,ten_danh_muc',
            'slug' => 'nullable|string|max:180',
            'mo_ta' => 'nullable|string|max:1000',
            'trang_thai' => 'nullable|in:dang_dung,ngung_dung,đang dùng,ngưng dùng',
        ], [
            'ten_danh_muc.required' => 'Vui lòng nhập tên danh mục.',
            'ten_danh_muc.unique' => 'Tên danh mục đã tồn tại.',
            'trang_thai.in' => 'Trạng thái danh mục không hợp lệ.',
        ]);

        $slugSource = trim((string) ($validated['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = trim($validated['ten_danh_muc']);
        }

        $category = DanhMuc::create([
            'ten_danh_muc' => trim($validated['ten_danh_muc']),
            'slug' => $this->makeUniqueSlug($slugSource),
            'mo_ta' => $validated['mo_ta'] ?? null,
            'trang_thai' => $this->toDbTrangThai($validated['trang_thai'] ?? null),
        ]);

        return back()->with('success', "Đã thêm danh mục {$category->ten_danh_muc}.");
    }

    public function update(Request $request, int $id)
    {
        $category = DanhMuc::findOrFail($id);

        $validated = $request->validate([
            'ten_danh_muc' => "required|string|max:150|unique:danh_muc,ten_danh_muc,{$id}",
            'slug' => 'nullable|string|max:180',
            'mo_ta' => 'nullable|string|max:1000',
            'trang_thai' => 'nullable|in:dang_dung,ngung_dung,đang dùng,ngưng dùng',
        ], [
            'ten_danh_muc.required' => 'Vui lòng nhập tên danh mục.',
            'ten_danh_muc.unique' => 'Tên danh mục đã tồn tại.',
            'trang_thai.in' => 'Trạng thái danh mục không hợp lệ.',
        ]);

        $slugSource = trim((string) ($validated['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = trim($validated['ten_danh_muc']);
        }

        $category->update([
            'ten_danh_muc' => trim($validated['ten_danh_muc']),
            'slug' => $this->makeUniqueSlug($slugSource, $category->id),
            'mo_ta' => $validated['mo_ta'] ?? null,
            'trang_thai' => $this->toDbTrangThai($validated['trang_thai'] ?? null),
        ]);

        return back()->with('success', "Đã cập nhật danh mục {$category->ten_danh_muc}.");
    }

    public function destroy(int $id)
    {
        $category = DanhMuc::withCount('sanPham')->findOrFail($id);

        if ($category->san_pham_count > 0) {
            return back()->with('error', 'Danh mục đang có sản phẩm, không thể xóa.');
        }

        try {
            $name = $category->ten_danh_muc;
            $category->delete();
            return back()->with('success', "Đã xóa danh mục {$name}.");
        } catch (QueryException) {
            return back()->with('error', 'Không thể xóa danh mục do dữ liệu liên quan.');
        }
    }
}
