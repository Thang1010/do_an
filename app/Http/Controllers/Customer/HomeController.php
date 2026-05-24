<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSanPham;
use App\Models\DanhMuc;
use App\Models\SanPham;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $categories = DanhMuc::orderBy('ten_danh_muc')->get();

        $categorySlugs = $categories->mapWithKeys(function ($category) {
            $slug = $category->slug ?: Str::slug($category->ten_danh_muc);
            return [$category->id => $slug];
        });

        $categoryImages = SanPham::whereIn('danh_muc_id', $categories->pluck('id'))
            ->whereNotNull('hinh_anh_chinh')
            ->orderByDesc('noi_bat')
            ->orderByDesc('created_at')
            ->get(['danh_muc_id', 'hinh_anh_chinh'])
            ->groupBy('danh_muc_id')
            ->map(function ($items) {
                return asset('storage/' . $items->first()->hinh_anh_chinh);
            });

        $from = Carbon::now()->subDays(6)->startOfDay();

        $drinkSlugs = ['do-nong', 'do-lanh', 'do-uong', 'ca-phe', 'tra'];
        $dessertSlugs = ['do-an-vat', 'an-vat', 'banh', 'trang-mieng', 'do-ngot'];

        $drinkCategoryIds = $categorySlugs->filter(fn($slug) => in_array($slug, $drinkSlugs, true))
            ->keys()
            ->values()
            ->all();

        $dessertCategoryIds = $categorySlugs->filter(fn($slug) => in_array($slug, $dessertSlugs, true))
            ->keys()
            ->values()
            ->all();

        $bestDrinks = $this->bestSellers($drinkCategoryIds, $from, 8);
        $bestDesserts = $this->bestSellers($dessertCategoryIds, $from, 8);

        if ($bestDrinks->isEmpty()) {
            $bestDrinks = $this->bestSellers([], $from, 8);
        }

        if ($bestDesserts->isEmpty()) {
            $bestDesserts = $this->bestSellers([], $from, 8);
        }

        $testimonials = DanhGiaSanPham::with(['nguoiDung', 'sanPham'])
            ->latest()
            ->limit(3)
            ->get();

        return view('customer.dashboard', compact(
            'categories',
            'categoryImages',
            'categorySlugs',
            'bestDrinks',
            'bestDesserts',
            'testimonials'
        ));
    }

    private function bestSellers(array $categoryIds, Carbon $from, int $limit)
    {
        $query = DB::table('chi_tiet_don_hang')
            ->join('don_hang', 'don_hang.id', '=', 'chi_tiet_don_hang.don_hang_id')
            ->join('san_pham', 'san_pham.id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->whereNotIn('don_hang.trang_thai_don', ['huy', 'đã hủy'])
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
                ->orderByDesc('noi_bat')
                ->latest()
                ->limit($limit)
                ->get();
        }

        $products = SanPham::whereIn('id', $topIds)->get()->keyBy('id');

        return $topIds->map(fn($id) => $products->get($id))
            ->filter()
            ->values();
    }
}
