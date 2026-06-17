<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSanPham;
use App\Models\DonHang;
use App\Models\NguoiDung;
use App\Models\PhienChat;
use App\Models\SanPham;
use App\Models\TinNhanChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('customer.chatbot.index');
    }

    public function history(Request $request)
    {
        $sessions = $request->user()
            ->phienChat()
            ->has('tinNhanChat')
            ->withCount('tinNhanChat')
            ->with(['tinNhanChat' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->paginate(15);

        return view('customer.chat-history.index', compact('sessions'));
    }

    public function historyShow(Request $request, int $id)
    {
        $session = $request->user()
            ->phienChat()
            ->with(['tinNhanChat' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($id);

        return view('customer.chat-history.show', compact('session'));
    }

    public function sessionList(Request $request)
    {
        $user = $this->resolveUser();
        if (! $user) {
            return response()->json(['sessions' => []]);
        }

        $activeId = $request->session()->get('chat_session_id');

        $sessions = $user->phienChat()
            ->has('tinNhanChat')
            ->withCount('tinNhanChat')
            ->with(['tinNhanChat' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($s) use ($activeId) {
                $firstUser = $s->tinNhanChat->firstWhere('nguoi_gui', 'người dùng');
                $preview = $firstUser?->noi_dung ?? optional($s->tinNhanChat->first())->noi_dung ?? 'Cuộc trò chuyện';
                $last = $s->tinNhanChat->last();

                return [
                    'id' => $s->id,
                    'preview' => Str::limit($preview, 60),
                    'count' => $s->tin_nhan_chat_count,
                    'time' => optional($last?->created_at ?? $s->created_at)->format('d/m/Y H:i'),
                    'active' => (int) $s->id === (int) $activeId,
                ];
            });

        return response()->json(['sessions' => $sessions]);
    }

    public function sessionMessages(Request $request, int $id)
    {
        $user = $this->resolveUser();
        if (! $user) {
            return response()->json(['error' => 'Bạn cần đăng nhập để xem lịch sử.'], 403);
        }

        $session = $user->phienChat()
            ->with(['tinNhanChat' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($id);

        // Đặt phiên này làm phiên đang hoạt động để khách chat tiếp
        $request->session()->put('chat_session_id', $session->id);

        $messages = $session->tinNhanChat->map(fn ($m) => [
            'role' => $m->nguoi_gui === 'người dùng' ? 'user' : 'bot',
            'content' => $m->noi_dung,
            'time' => $m->created_at?->format('H:i'),
        ]);

        return response()->json(['messages' => $messages]);
    }

    public function sessionReset(Request $request)
    {
        // Quên phiên hiện tại để bắt đầu cuộc trò chuyện mới
        $request->session()->forget('chat_session_id');

        return response()->json(['ok' => true]);
    }

    public function suggest(Request $request)
    {
        $apiKey = env('OPENAI_API_KEY');
        if (! $apiKey) {
            return response()->json([
                'error' => 'OpenAI API key chưa được cấu hình.',
            ], 500);
        }

        $session = $this->resolveChatSession($request);
        $user = $this->resolveUser();

        $favoriteItems = $this->getFavoriteItems($user);
        $candidates = $this->getBestSellerPerCategory();

        $reply = $this->buildSuggestionReply($candidates, $apiKey, $favoriteItems);
        $this->storeChatMessage($session, 'chatbot', $reply);

        $products = $this->formatCandidateProducts($candidates);

        return response()->json([
            'reply' => $reply,
            'context' => [
                'candidates' => $products,
            ],
            'products' => $products,
        ]);
    }

    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array',
            'context' => 'nullable|array',
        ]);

        $apiKey = env('OPENAI_API_KEY');
        if (! $apiKey) {
            return response()->json([
                'error' => 'OpenAI API key chưa được cấu hình.',
            ], 500);
        }

        $session = $this->resolveChatSession($request);
        $user = $this->resolveUser();
        $favoriteItems = $this->getFavoriteItems($user);
        $pastOrdersText = $this->getPastOrdersText($user);
        $pastReviewsText = $this->getPastReviewsText($user);

        $messageText = (string) $request->input('message');
        $this->storeChatMessage($session, 'người dùng', $messageText);

        $context = (array) $request->input('context', []);
        $menuContext = $this->buildMenuContext();
        $favoriteText = $this->formatFavoriteText($favoriteItems);

        $weatherContext = $this->getWeatherContext();

        $systemPrompt = "Bạn là trợ lý tư vấn của quán cafe XM Coffee. Trả lời tiếng Việt, ngắn gọn, thân thiện.\n";
        $systemPrompt .= "TỪ CHỐI TẤT CẢ CÁC CÂU HỎI NGOÀI LỀ (toán học, văn học, lập trình, triết học...). Lịch sự báo rằng bạn chỉ hỗ trợ tư vấn đồ uống và menu của quán.\n";
        $systemPrompt .= "KHÔNG ĐƯỢC TIẾT LỘ CÔNG THỨC của các món ăn/đồ uống dưới bất kỳ hình thức nào. Công thức chỉ để bạn ngầm hiểu nguyên liệu.\n";

        if ($menuContext === '') {
            $systemPrompt .= "Hiện tại cửa hàng chưa có món nào. Xin hãy thông báo và xin lỗi khách, không gợi ý gì thêm.\n";
        } else {
            $systemPrompt .= "TUYỆT ĐỐI CHỈ ĐƯỢC TƯ VẤN CÁC MÓN CÓ CHÍNH XÁC TÊN TRONG DANH SÁCH MENU BÊN DƯỚI. KHÔNG ĐƯỢC BỊA MÓN MỚI.\n"
                ."Nếu khách hỏi món không có, hãy từ chối lịch sự và gợi ý món gần giống CÓ TRONG THỰC ĐƠN.\n"
                ."Dựa vào danh sách nguyên liệu: nếu khách có bệnh lý (tiểu đường, dạ dày, dị ứng...), hãy phân tích ngầm nguyên liệu nên tránh (đường, sữa, cafein...) và gợi ý món phù hợp nhất TRONG MENU.\n"
                ."Nếu gợi ý món có thể điều chỉnh (ví dụ: bớt đường, không đá), hãy kèm thêm một lệnh ghi chú ẩn ở cuối câu trả lời theo cú pháp: `[GHI CHÚ: Tên món chính xác = Nội dung ghi chú]`. Ví dụ: `[GHI CHÚ: Cà phê đen = Ít đường, không đá]`.\n";
        }

        if ($weatherContext !== '') {
            $systemPrompt .= "\n".$weatherContext."\n";
        }

        $systemPrompt .= "\nMenu hiện có:\n".$menuContext;

        if ($favoriteText !== '') {
            $systemPrompt .= "\n\nMón yêu thích của khách (dựa vào danh sách yêu thích): {$favoriteText}.";
        }
        if ($pastOrdersText !== '') {
            $systemPrompt .= "\nCác món khách đã từng đặt gần đây: {$pastOrdersText}. (Có thể gợi ý khách đặt lại nếu phù hợp).";
        }
        if ($pastReviewsText !== '') {
            $systemPrompt .= "\nCác món khách từng đánh giá tốt: {$pastReviewsText}.";
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($this->normalizeHistory($request->input('history', [])) as $item) {
            $messages[] = $item;
        }

        $messages[] = [
            'role' => 'user',
            'content' => $messageText,
        ];

        $reply = $this->callOpenAi($apiKey, $messages, 450, 0.6);
        if (! $reply) {
            return response()->json([
                'error' => 'Không thể kết nối OpenAI. Vui lòng thử lại sau.',
            ], 502);
        }

        $chatbotNotes = $context['chatbot_notes'] ?? [];
        $reply = preg_replace_callback('/\[GHI CHÚ:\s*(.*?)\s*=\s*(.*?)\]/iu', function ($matches) use (&$chatbotNotes) {
            $productName = mb_strtolower(trim($matches[1]));
            $chatbotNotes[$productName] = trim($matches[2]);

            return ''; // Xóa khỏi tin nhắn hiển thị cho người dùng
        }, $reply);

        $reply = trim($reply);
        $context['chatbot_notes'] = $chatbotNotes;

        $this->storeChatMessage($session, 'chatbot', $reply);

        $shouldSuggest = $this->shouldSuggestProducts($messageText);
        $products = $context['candidates'] ?? [];
        if ($shouldSuggest || empty($products)) {
            $candidates = $this->pickSuggestions($this->buildMenuItems(), $favoriteItems);
            $products = $this->formatCandidateProducts($candidates);
            $context['candidates'] = $products;
        }

        return response()->json([
            'reply' => $reply,
            'context' => $context,
            'products' => $products,
        ]);
    }

    private function resolveUser(): ?NguoiDung
    {
        $guard = Auth::guard('nguoi_dung');

        return $guard->check() ? $guard->user() : null;
    }

    private function resolveChatSession(Request $request): PhienChat
    {
        $sessionId = $request->session()->get('chat_session_id');
        $user = $this->resolveUser();

        if ($sessionId) {
            $existing = PhienChat::whereKey($sessionId)
                ->where('trang_thai', 'đang hoạt động')
                ->first();

            if ($existing) {
                if ($user && $existing->nguoi_dung_id === null) {
                    $existing->update([
                        'nguoi_dung_id' => $user->id,
                        'kenh_chat' => 'website khách hàng',
                    ]);
                }

                return $existing;
            }
        }

        $session = PhienChat::create([
            'nguoi_dung_id' => $user?->id,
            'kenh_chat' => $user ? 'website khách hàng' : 'website khách vãng lai',
            'trang_thai' => 'đang hoạt động',
        ]);

        $request->session()->put('chat_session_id', $session->id);

        return $session;
    }

    private function buildMenuItems()
    {
        return SanPham::with('danhMuc')
            ->whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
            ->orderByDesc('noi_bat')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get([
                'id',
                'danh_muc_id',
                'ten_san_pham',
                'gia_goc',
                'gia_khuyen_mai',
                'hinh_anh',
                'noi_bat',
                'nhiet_do',
            ])
            ->map(fn ($item) => $this->mapMenuItem($item));
    }

    private function getFavoriteItems(?NguoiDung $user): array
    {
        if (! $user) {
            return [];
        }

        return $user->sanPhamYeuThich()
            ->with('danhMuc')
            ->orderByDesc('san_pham_yeu_thich.created_at')
            ->limit(6)
            ->get([
                'san_pham.id',
                'san_pham.danh_muc_id',
                'san_pham.ten_san_pham',
                'san_pham.gia_goc',
                'san_pham.gia_khuyen_mai',
                'san_pham.hinh_anh',
                'san_pham.nhiet_do',
            ])
            ->map(fn ($item) => $this->mapMenuItem($item))
            ->all();
    }

    private function getPastOrdersText(?NguoiDung $user): string
    {
        if (! $user) {
            return '';
        }
        $orders = DonHang::with('chiTietDonHang.sanPham')
            ->where('nguoi_dung_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();
        if ($orders->isEmpty()) {
            return '';
        }

        $products = [];
        foreach ($orders as $order) {
            foreach ($order->chiTietDonHang as $detail) {
                if ($detail->sanPham) {
                    $products[] = $detail->sanPham->ten_san_pham;
                }
            }
        }
        $products = array_unique($products);

        return implode(', ', array_slice($products, 0, 10));
    }

    private function getPastReviewsText(?NguoiDung $user): string
    {
        if (! $user) {
            return '';
        }
        $reviews = DanhGiaSanPham::with('sanPham')
            ->where('nguoi_dung_id', $user->id)
            ->where('so_sao', '>=', 4)
            ->latest()
            ->limit(5)
            ->get();
        if ($reviews->isEmpty()) {
            return '';
        }

        $products = [];
        foreach ($reviews as $review) {
            if ($review->sanPham) {
                $products[] = $review->sanPham->ten_san_pham.' ('.$review->so_sao.' sao)';
            }
        }

        return implode(', ', array_unique($products));
    }

    private function mapMenuItem(SanPham $item): array
    {
        $category = $item->danhMuc?->ten_danh_muc;
        $slug = $item->danhMuc?->slug ?: ($category ? Str::slug($category) : '');

        return [
            'id' => $item->id,
            'name' => $item->ten_san_pham,
            'price' => $item->gia_khuyen_mai ?? $item->gia_goc,
            'category' => $category,
            'slug' => $slug,
            'image_url' => $item->image_url,
            'nhiet_do' => $item->nhiet_do,
        ];
    }

    private function getBestSellerPerCategory(): array
    {
        // Count sales per product from order details
        $salesMap = DB::table('chi_tiet_don_hang')
            ->select('san_pham_id', DB::raw('SUM(so_luong) as total_sold'))
            ->groupBy('san_pham_id')
            ->pluck('total_sold', 'san_pham_id')
            ->toArray();

        $products = SanPham::with('danhMuc')
            ->whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
            ->get(['id', 'danh_muc_id', 'ten_san_pham', 'gia_goc', 'gia_khuyen_mai', 'hinh_anh', 'noi_bat', 'nhiet_do']);

        // Group by category, pick best-seller (most sold → featured → newest) per category
        $byCategory = $products->groupBy('danh_muc_id');
        $result = [];
        foreach ($byCategory as $catId => $catProducts) {
            $best = $catProducts->sortByDesc(function ($p) use ($salesMap) {
                return [$salesMap[$p->id] ?? 0, $p->noi_bat ? 1 : 0];
            })->first();
            if ($best) {
                $result[] = $this->mapMenuItem($best);
            }
        }

        return $result;
    }

    private function pickSuggestions($menuItems, array $favoriteItems = []): array
    {
        $drinkSlugs = ['do-nong', 'do-lanh', 'do-uong', 'ca-phe', 'tra', 'tra-sua', 'nuoc-ep', 'sinh-to', 'soda', 'da-xay'];
        $dessertSlugs = ['do-an-vat', 'an-vat', 'banh', 'trang-mieng', 'do-ngot'];
        $coldKeywords = ['đá', 'da', 'lạnh', 'lanh', 'sinh tố', 'sinh to', 'soda', 'nước ép', 'nuoc ep', 'trà sữa', 'tra sua', 'matcha', 'smoothie', 'juice'];
        $hotKeywords = ['nóng', 'nong', 'hot', 'espresso', 'latte', 'cappuccino', 'capuchino', 'americano', 'socola', 'ca cao', 'trà nóng', 'tra nong'];

        $groups = [
            'cold' => [],
            'hot' => [],
            'dessert' => [],
            'food' => [],
            'other' => [],
        ];

        foreach ($menuItems as $item) {
            $slug = $item['slug'] ?? '';
            $name = mb_strtolower((string) ($item['name'] ?? ''));

            $isDrinkCategory = $slug !== '' && (in_array($slug, $drinkSlugs, true)
                || Str::contains($slug, ['ca-phe', 'tra', 'do-uong', 'nuoc', 'sinh-to', 'soda']));

            $isDessertCategory = $slug !== '' && (in_array($slug, $dessertSlugs, true)
                || Str::contains($slug, ['banh', 'an-vat', 'trang-mieng', 'do-ngot', 'do-an']));

            if ($isDrinkCategory) {
                $isCold = $this->containsAny($name, $coldKeywords) || Str::contains($slug, ['lanh', 'da', 'da-xay']) || Str::contains($item['nhiet_do'] ?? '', 'lạnh');
                $isHot = $this->containsAny($name, $hotKeywords) || Str::contains($slug, ['nong']) || Str::contains($item['nhiet_do'] ?? '', 'nóng');

                if ($isCold && ! $isHot) {
                    $groups['cold'][] = $item;

                    continue;
                }

                if ($isHot && ! $isCold) {
                    $groups['hot'][] = $item;

                    continue;
                }

                $groups['other'][] = $item;

                continue;
            }

            if ($isDessertCategory) {
                $groups['dessert'][] = $item;

                continue;
            }

            $groups['food'][] = $item;
        }

        $picked = array_merge(
            $this->takeItems($favoriteItems, 3),
            $this->takeItems($groups['other'], 3),
            $this->takeItems($groups['dessert'], 2),
            $this->takeItems($groups['cold'], 1)
        );

        if (count($picked) < 4) {
            $fallback = array_merge($groups['cold'], $groups['hot'], $groups['dessert'], $groups['food'], $groups['other']);
            $picked = array_merge($picked, $this->takeItems($fallback, 6 - count($picked)));
        }

        return array_slice($this->uniqueItems($picked), 0, 6);
    }

    private function buildSuggestionReply(array $candidates, string $apiKey, array $favoriteItems = []): string
    {
        if (empty($candidates)) {
            return 'Hiện tại cửa hàng chưa có món nào trên thực đơn. Bạn vui lòng quay lại sau nhé!';
        }

        $candidateText = $this->formatCandidateText($candidates);
        $favoriteText = $this->formatFavoriteText($favoriteItems);
        $weatherContext = $this->getWeatherContext();

        $systemPrompt = "Bạn là trợ lý XM Coffee. Hãy gợi ý 4-6 món từ danh sách được cung cấp.\n"
            ."TUYỆT ĐỐI CHỈ SỬ DỤNG ĐÚNG TÊN MÓN TRONG DANH SÁCH ĐƯỢC CUNG CẤP, KHÔNG ĐƯỢC BỊA THÊM HOẶC THAY ĐỔI TÊN MÓN.\n"
            .'Trả lời tiếng Việt, thân thiện, có danh sách gạch đầu dòng và hỏi thêm 1-2 câu nếu khách chưa chọn.';

        if ($weatherContext !== '') {
            $systemPrompt .= ' '.$weatherContext;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => trim("Danh sách món gợi ý:\n{$candidateText}\n".($favoriteText !== '' ? "\nMón yêu thích của khách: {$favoriteText}" : '')),
            ],
        ];

        $reply = $this->callOpenAi($apiKey, $messages, 420, 0.6);
        if ($reply) {
            return $reply;
        }

        $lines = [
            'Gợi ý món phù hợp:',
        ];

        foreach ($this->formatCandidates($candidates) as $item) {
            $lines[] = "- {$item['name']} ({$item['price']})";
        }

        $lines[] = 'Bạn thích đồ uống nóng hay lạnh? Có muốn thêm bánh/ngọt không?';

        return implode("\n", $lines);
    }

    private function formatCandidateText(array $candidates): string
    {
        $lines = [];
        foreach ($this->formatCandidates($candidates) as $item) {
            $lines[] = "- {$item['name']} ({$item['category']}) - {$item['price']}";
        }

        return implode("\n", $lines);
    }

    private function formatFavoriteText(array $favoriteItems): string
    {
        if (empty($favoriteItems)) {
            return '';
        }

        $names = array_map(fn ($item) => $item['name'] ?? null, $favoriteItems);
        $names = array_values(array_filter($names, fn ($name) => is_string($name) && $name !== ''));

        return implode(', ', array_slice($names, 0, 6));
    }

    private function formatCandidates(array $candidates): array
    {
        return array_map(function ($item) {
            $price = $item['price'] ?? null;
            $priceText = $price !== null
                ? number_format((float) $price, 0, ',', '.').'đ'
                : 'Giá đang cập nhật';

            return [
                'name' => $item['name'] ?? 'Món mới',
                'category' => $item['category'] ?? 'Menu',
                'price' => $priceText,
            ];
        }, $candidates);
    }

    private function formatCandidateProducts(array $candidates): array
    {
        $productIds = array_values(array_filter(array_column($candidates, 'id')));

        // Load sizes for all candidate products in one query
        $sizesMap = [];
        if (! empty($productIds)) {
            $productsWithSizes = SanPham::with('kichCo')->whereIn('id', $productIds)->get();
            foreach ($productsWithSizes as $product) {
                $basePrice = (float) ($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);
                foreach ($product->kichCo as $kc) {
                    $sizesMap[$product->id][] = [
                        'id' => $kc->id,
                        'name' => $kc->ten_kich_co ?? ('Size '.$kc->id),
                        'code' => $kc->ma_kich_co ?? '',
                        'price' => $basePrice * (float) ($kc->he_so_gia ?? 1),
                    ];
                }
            }
        }

        $products = [];
        foreach ($candidates as $item) {
            $id = $item['id'] ?? null;
            if (! $id) {
                continue;
            }

            $price = $item['price'] ?? null;
            $priceText = $price !== null
                ? number_format((float) $price, 0, ',', '.').'đ'
                : 'Giá đang cập nhật';

            $products[] = [
                'id' => $id,
                'name' => $item['name'] ?? 'Món mới',
                'price' => $priceText,
                'image_url' => $item['image_url'] ?? asset('images/ca_phe_nau_da.jpg'),
                'sizes' => $sizesMap[$id] ?? [],
                'nhiet_do' => $item['nhiet_do'] ?? null,
            ];
        }

        return $products;
    }

    private function buildMenuContext(): string
    {
        $items = SanPham::with(['danhMuc', 'congThucSanPham.nguyenLieu'])
            ->whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
            ->orderByDesc('noi_bat')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        $lines = [];
        foreach ($items as $item) {
            $price = $item->gia_khuyen_mai ?? $item->gia_goc;
            $priceText = $price !== null ? number_format((float) $price, 0, ',', '.').'đ' : 'Giá đang cập nhật';
            $category = $item->danhMuc?->ten_danh_muc ?? 'Menu';

            $ingredients = $item->congThucSanPham->map(fn ($ct) => $ct->nguyenLieu?->ten_nguyen_lieu)->filter()->implode(', ');
            $ingredientText = $ingredients ? " (Nguyên liệu: {$ingredients})" : '';

            $tempText = $item->nhiet_do ? " - Nhiệt độ: {$item->nhiet_do}" : '';

            $lines[] = "- {$item->ten_san_pham} ({$category}) - {$priceText}{$tempText}{$ingredientText}";
        }

        return implode("\n", $lines);
    }

    private function normalizeHistory(array $history): array
    {
        $normalized = [];
        foreach (array_slice($history, -8) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = isset($item['content']) ? trim((string) $item['content']) : '';
            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }

            $normalized[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        return $normalized;
    }

    private function shouldSuggestProducts(string $message): bool
    {
        $text = mb_strtolower($message);
        $keywords = [
            'gợi ý',
            'goi y',
            'đề xuất',
            'de xuat',
            'menu',
            'thực đơn',
            'thuc don',
            'món',
            'mon',
            'uống',
            'uong',
            'ăn',
            'an',
            'ít ngọt',
            'it ngot',
            'lạnh',
            'lanh',
            'nóng',
            'nong',
        ];

        return $this->containsAny($text, $keywords);
    }

    private function storeChatMessage(PhienChat $session, string $sender, string $content): void
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return;
        }

        TinNhanChat::create([
            'phien_chat_id' => $session->id,
            'nguoi_gui' => $sender,
            'noi_dung' => $trimmed,
            'created_at' => now(),
        ]);
    }

    private function callOpenAi(string $apiKey, array $messages, int $maxTokens = 400, float $temperature = 0.7): ?string
    {
        $model = env('OPENAI_MODEL', 'gpt-4o-mini');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::error('OpenAI Error: '.$response->body());

                return null;
            }

            $reply = $response->json('choices.0.message.content');

            return $reply ? trim((string) $reply) : null;
        } catch (\Throwable $exception) {
            Log::error('OpenAI Exception: '.$exception->getMessage());

            return null;
        }
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function takeItems(array $items, int $count): array
    {
        if ($count <= 0 || empty($items)) {
            return [];
        }

        shuffle($items);

        return array_slice($items, 0, $count);
    }

    private function uniqueItems(array $items): array
    {
        $unique = [];
        $ids = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if ($id !== null && in_array($id, $ids, true)) {
                continue;
            }
            if ($id !== null) {
                $ids[] = $id;
            }
            $unique[] = $item;
        }

        return $unique;
    }

    private function getWeatherContext(): string
    {
        $apiKey = config('services.openweathermap.key');
        $city = config('services.openweathermap.city', 'Hanoi,vn');

        if (! $apiKey) {
            return '';
        }

        $cacheKey = 'current_weather_'.Str::slug($city);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $city,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'vi',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $temp = $data['main']['temp'] ?? null;
                $description = $data['weather'][0]['description'] ?? null;

                if ($temp !== null && $description !== null) {
                    $context = "Thời tiết hiện tại ở khu vực quán đang là {$temp}°C, {$description}. Ở ngay câu đầu tiên, hãy mở lời chào bằng cách nhắc đến thời tiết này một cách thân thiện (ví dụ: 'Trời hôm nay đang nóng {$temp}°C...'), sau đó hãy dựa vào thời tiết để tư vấn món đồ uống phù hợp nhất cho khách hàng.";
                    Cache::put($cacheKey, $context, 600);

                    return $context;
                }
            }
        } catch (\Throwable $e) {
            // Bỏ qua nếu lỗi kết nối
        }

        return '';
    }
}
