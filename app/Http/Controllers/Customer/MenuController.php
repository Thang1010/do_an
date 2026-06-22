<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\DanhGiaSanPham;
use App\Models\DanhMuc;
use App\Models\SanPham;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        // Load all active categories with product count
        $categories = DanhMuc::withCount(['sanPham' => function ($q) {
            $q->whereIn('trang_thai_ban', ['dang_ban', 'đang bán']);
        }])->orderBy('ten_danh_muc')->get();

        // Build category slugs map
        $categorySlugs = $categories->mapWithKeys(function ($category) {
            $slug = $category->slug ?: Str::slug($category->ten_danh_muc);
            return [$category->id => $slug];
        });

        // Build category images map
        $categoryImages = SanPham::whereIn('danh_muc_id', $categories->pluck('id'))
            ->whereNotNull('hinh_anh')
            ->orderByDesc('noi_bat')
            ->orderByDesc('created_at')
            ->get(['danh_muc_id', 'hinh_anh'])
            ->groupBy('danh_muc_id')
            ->map(function ($items) {
                $img = $items->first()->hinh_anh;
                if (Str::startsWith($img, ['http://', 'https://'])) {
                    return $img;
                }
                return asset('storage/' . $img);
            });

        // Determine active category
        $categorySlug = $request->query('category');
        $activeCategory = null;

        if ($categorySlug) {
            $activeCategory = $categories->first(function ($cat) use ($categorySlug, $categorySlugs) {
                return ($categorySlugs[$cat->id] ?? Str::slug($cat->ten_danh_muc)) === $categorySlug;
            });
        }

        // Default to first category
        if (!$activeCategory && $categories->isNotEmpty()) {
            $activeCategory = $categories->first();
        }

        // Search
        $search = trim((string) $request->query('search', ''));

        // Build products query
        $productsQuery = SanPham::with(['danhMuc', 'kichCo'])
            ->whereIn('trang_thai_ban', ['dang_ban', 'đang bán']);

        if (auth()->check()) {
            $productsQuery->withExists(['nguoiDungYeuThich as is_favorite' => function ($q) {
                $q->where('nguoi_dung_id', auth()->id());
            }])->orderByDesc('is_favorite');
        }

        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('ten_san_pham', 'like', "%{$search}%")
                    ->orWhere('mo_ta', 'like', "%{$search}%");
            });
        } elseif ($activeCategory) {
            $productsQuery->where('danh_muc_id', $activeCategory->id);
        }

        $products = $productsQuery->orderByDesc('noi_bat')->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('customer.menu.index', compact(
            'categories',
            'categorySlugs',
            'categoryImages',
            'activeCategory',
            'products',
            'search'
        ));
    }

    public function show(int $id)
    {
        $product = SanPham::with(['danhMuc', 'kichCo', 'danhGiaSanPham.nguoiDung'])->findOrFail($id);

        $avgRating = $product->danhGiaSanPham->avg('so_sao') ?? 0;

        // Khách chỉ được đánh giá nếu đã mua (đã thanh toán) sản phẩm này VÀ còn trong ngày mua.
        $hasBought = auth()->check()
            && $this->latestPaidOrderDetail(auth()->id(), $product->id) !== null;

        // Đã từng mua nhưng đã qua ngày mua → hết hạn đánh giá (để hiển thị thông báo phù hợp).
        $reviewExpired = auth()->check()
            && ! $hasBought
            && $this->hasEverBoughtProduct(auth()->id(), $product->id);

        $related = SanPham::where('danh_muc_id', $product->danh_muc_id)
            ->where('id', '!=', $product->id)
            ->whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
            ->withAvg('danhGiaSanPham as avg_rating', 'so_sao')
            ->limit(4)
            ->get();

        return view('customer.menu.show', compact('product', 'related', 'avgRating', 'hasBought', 'reviewExpired'));
    }

    public function storeReview(Request $request, int $id)
    {
        if (!auth()->check()) {
            return redirect()->route('auth.login')->with('error', 'Bạn cần đăng nhập để đánh giá sản phẩm.');
        }

        $request->validate([
            'so_sao' => 'required|integer|min:1|max:5',
            'noi_dung' => 'nullable|string|max:1000',
        ]);

        $product = SanPham::findOrFail($id);

        // Chỉ cho phép đánh giá khi khách đã mua (đã thanh toán) VÀ còn trong ngày mua hàng.
        $chiTiet = $this->latestPaidOrderDetail(auth()->id(), $product->id);

        if (!$chiTiet) {
            $message = $this->hasEverBoughtProduct(auth()->id(), $product->id)
                ? 'Đã quá thời hạn đánh giá. Bạn chỉ có thể đánh giá sản phẩm trong ngày mua hàng.'
                : 'Bạn cần mua sản phẩm này trước khi đánh giá.';

            return back()->with('error', $message);
        }

        // 1 đánh giá / khách / sản phẩm — mua lại & đánh giá tiếp sẽ ghi đè cái cũ,
        // đồng thời cập nhật đơn hàng gần nhất mà khách đã mua món này.
        DanhGiaSanPham::updateOrCreate(
            ['nguoi_dung_id' => auth()->id(), 'san_pham_id' => $product->id],
            [
                'so_sao' => $request->so_sao,
                'noi_dung' => $request->noi_dung,
                'don_hang_id' => $chiTiet->don_hang_id,
            ]
        );

        return back()->with('success', 'Đánh giá của bạn đã được ghi nhận!');
    }

    /**
     * Lấy dòng chi tiết đơn hàng (đã thanh toán) mà khách đã mua sản phẩm này
     * VÀ đơn hàng được tạo trong NGÀY HÔM NAY (giới hạn thời gian đánh giá).
     * Đơn mua ngày nào chỉ được đánh giá trong ngày đó; qua ngày khác trả về null.
     */
    private function latestPaidOrderDetail(int $userId, int $productId): ?ChiTietDonHang
    {
        return ChiTietDonHang::where('san_pham_id', $productId)
            ->where('trang_thai_thanh_toan', 'đã thanh toán')
            ->whereHas('donHang', fn($q) => $q->where('nguoi_dung_id', $userId)
                ->whereDate('created_at', today()))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Khách đã từng mua (đã thanh toán) sản phẩm này hay chưa (bất kể ngày nào).
     * Dùng để phân biệt "chưa mua" với "đã mua nhưng quá hạn đánh giá".
     */
    private function hasEverBoughtProduct(int $userId, int $productId): bool
    {
        return ChiTietDonHang::where('san_pham_id', $productId)
            ->where('trang_thai_thanh_toan', 'đã thanh toán')
            ->whereHas('donHang', fn($q) => $q->where('nguoi_dung_id', $userId))
            ->exists();
    }

    public function toggleFavorite(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.'], 401);
        }

        $user = auth()->user();
        $product = SanPham::findOrFail($id);

        $isFavorite = $user->sanPhamYeuThich()->where('san_pham_id', $product->id)->exists();

        if ($isFavorite) {
            $user->sanPhamYeuThich()->detach($product->id);
            $status = false;
        } else {
            $user->sanPhamYeuThich()->attach($product->id);
            $status = true;
        }

        return response()->json(['success' => true, 'is_favorite' => $status]);
    }
}
