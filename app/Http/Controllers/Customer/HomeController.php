<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\DanhGiaSanPham;
use App\Models\DanhMuc;
use App\Models\SanPham;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function about()
    {
        return view('customer.home.about');
    }

    public function contact()
    {
        return view('customer.home.contact');
    }

    public function newsletter()
    {
        return back()->with('success', 'Đăng ký thành công! Mã giảm giá 15% đã được gửi đến email của bạn.');
    }

    public function orderTable($table)
    {
        // QR tĩnh tại bàn: {table} là id bàn. Gắn bàn vào phiên để giỏ hàng tự
        // điền + khoá (1 bàn = 1 QR). Không áp geofencing — dựa vào thanh toán trước.
        $banAn = BanAn::find($table);
        if (! $banAn || $banAn->trang_thai === 'ngưng sử dụng') {
            return redirect()->route('menu.index')
                ->with('error', 'Bàn không hợp lệ hoặc đang ngưng sử dụng.');
        }

        session(['qr_ban_an_id' => $banAn->id]);

        return view('customer.menu.index', ['tableNumber' => $banAn->so_ban]);
    }

    public function index()
    {
        $categories = DanhMuc::orderBy('ten_danh_muc')->get();

        $categorySlugs = $categories->mapWithKeys(function ($category) {
            $slug = $category->slug ?: Str::slug($category->ten_danh_muc);
            return [$category->id => $slug];
        });

        $categoryImages = SanPham::whereIn('danh_muc_id', $categories->pluck('id'))
            ->whereNotNull('hinh_anh')
            ->orderByDesc('noi_bat')
            ->orderByDesc('created_at')
            ->get(['danh_muc_id', 'hinh_anh'])
            ->groupBy('danh_muc_id')
            ->map(function ($items) {
                return asset('storage/' . $items->first()->hinh_anh);
            });

        $from = Carbon::now()->subDays(7)->startOfDay();

        $bestSellers = $this->bestSellers([], $from, 10);

        // Sản phẩm NỔI BẬT (do quán đánh dấu) để hiển thị mục riêng ở trang chủ.
        $featuredProducts = SanPham::whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
            ->where('noi_bat', true)
            ->withAvg('danhGiaSanPham as avg_rating', 'so_sao')
            ->with('kichCo')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // Lấy đánh giá từ top 10 đồ uống bán chạy: chỉ hiển thị đánh giá tích cực,
        // mỗi sản phẩm 1 đánh giá có số sao cao nhất. Sản phẩm không có đánh giá
        // tích cực sẽ không xuất hiện ở trang chủ.
        $testimonialsProductIds = $bestSellers->take(10)->pluck('id')
            ->unique()
            ->values();

        if ($testimonialsProductIds->isNotEmpty()) {
            $testimonials = DanhGiaSanPham::with(['nguoiDung', 'sanPham'])
                ->whereIn('san_pham_id', $testimonialsProductIds)
                ->where('phan_tich_cam_xuc', 'Tích cực')
                ->orderByDesc('so_sao')
                ->latest()
                ->get()
                ->groupBy('san_pham_id')
                ->map(fn($reviews) => $reviews->first())
                ->values();
        } else {
            // Dự phòng: không có sản phẩm bán chạy → vẫn ưu tiên đánh giá tích cực
            // (mỗi sản phẩm 1 đánh giá sao cao nhất) để giữ ấn tượng tốt.
            $testimonials = DanhGiaSanPham::with(['nguoiDung', 'sanPham'])
                ->where('phan_tich_cam_xuc', 'Tích cực')
                ->orderByDesc('so_sao')
                ->latest()
                ->get()
                ->groupBy('san_pham_id')
                ->map(fn($reviews) => $reviews->first())
                ->take(9)
                ->values();
        }

        return view('customer.dashboard', compact(
            'categories',
            'categoryImages',
            'categorySlugs',
            'bestSellers',
            'featuredProducts',
            'testimonials'
        ));
    }

    private function bestSellers(array $categoryIds, Carbon $from, int $limit)
    {
        $query = DB::table('chi_tiet_don_hang')
            ->join('don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->join('san_pham', 'san_pham.id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->where('don_hang.created_at', '>=', $from)
            ->whereIn('san_pham.trang_thai_ban', ['dang_ban', 'đang bán']);

        if (!empty($categoryIds)) {
            $query->whereIn('san_pham.danh_muc_id', $categoryIds);
        }

        $topIds = $query->groupBy('chi_tiet_don_hang.san_pham_id')
            ->orderByDesc(DB::raw('SUM(chi_tiet_don_hang.so_luong)'))
            ->limit($limit)
            ->pluck('chi_tiet_don_hang.san_pham_id');

        if ($topIds->isEmpty()) {
            return SanPham::whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
                ->when(!empty($categoryIds), fn($q) => $q->whereIn('danh_muc_id', $categoryIds))
                ->withAvg('danhGiaSanPham as avg_rating', 'so_sao')
                ->with('kichCo')
                ->orderByDesc('noi_bat')
                ->latest()
                ->limit($limit)
                ->get();
        }

        $products = SanPham::whereIn('id', $topIds)
            ->withAvg('danhGiaSanPham as avg_rating', 'so_sao')
            ->with('kichCo')
            ->get()->keyBy('id');

        return $topIds->map(fn($id) => $products->get($id))
            ->filter()
            ->values();
    }
}
