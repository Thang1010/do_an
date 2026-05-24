<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\PhienChat;
use App\Models\SanPham;
use App\Models\TinNhanChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('customer.chatbot.index');
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

        $menuItems = $this->buildMenuItems();
        $favoriteItems = $this->getFavoriteItems($user);
        $candidates = $this->pickSuggestions($menuItems, $favoriteItems);

        $reply = $this->buildSuggestionReply($candidates, $apiKey, $favoriteItems);
        $this->storeChatMessage($session, 'chatbot', $reply, 'gợi ý sản phẩm');

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

        $messageText = (string) $request->input('message');
        $this->storeChatMessage($session, 'người dùng', $messageText, 'văn bản');

        $context = (array) $request->input('context', []);
        $menuContext = $this->buildMenuContext();
        $favoriteText = $this->formatFavoriteText($favoriteItems);

        $systemPrompt = "Bạn là trợ lý XM Coffee. Trả lời tiếng Việt, ngắn gọn, thân thiện. "
            . "Chỉ gợi ý món có trong menu dưới đây. Nếu khách chưa chọn được, hỏi 1-2 câu để làm rõ (nóng/lạnh, vị ngọt, ngân sách).\n\n"
            . "Menu hiện có:\n" . $menuContext;

        if ($favoriteText !== '') {
            $systemPrompt .= "\n\nMón yêu thích của khách: {$favoriteText}.";
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

        $this->storeChatMessage($session, 'chatbot', $reply, 'văn bản');

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
            'ma_phien' => (string) Str::uuid(),
            'kenh_chat' => $user ? 'website khách hàng' : 'website khách vãng lai',
            'tieu_de' => 'Chatbot XM Coffee',
            'bat_dau_luc' => now(),
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
                'hinh_anh_chinh',
                'noi_bat',
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
                'san_pham.hinh_anh_chinh',
            ])
            ->map(fn ($item) => $this->mapMenuItem($item))
            ->all();
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
        ];
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
                $isCold = $this->containsAny($name, $coldKeywords) || Str::contains($slug, ['lanh', 'da', 'da-xay']);
                $isHot = $this->containsAny($name, $hotKeywords) || Str::contains($slug, ['nong']);

                if ($isCold) {
                    $groups['cold'][] = $item;
                    continue;
                }

                if ($isHot) {
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
        $candidateText = $this->formatCandidateText($candidates);
        $favoriteText = $this->formatFavoriteText($favoriteItems);

        $messages = [
            [
                'role' => 'system',
                'content' => 'Bạn là trợ lý XM Coffee. Hãy gợi ý 4-6 món từ danh sách được cung cấp, không bịa thêm món. Trả lời tiếng Việt, thân thiện, có danh sách gạch đầu dòng và hỏi thêm 1-2 câu nếu khách chưa chọn.',
            ],
            [
                'role' => 'user',
                'content' => trim("Danh sách món gợi ý:\n{$candidateText}\n" . ($favoriteText !== '' ? "\nMón yêu thích của khách: {$favoriteText}" : '')),
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
                ? number_format((float) $price, 0, ',', '.') . 'đ'
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
        $products = [];
        foreach ($candidates as $item) {
            $id = $item['id'] ?? null;
            if (! $id) {
                continue;
            }

            $price = $item['price'] ?? null;
            $priceText = $price !== null
                ? number_format((float) $price, 0, ',', '.') . 'đ'
                : 'Giá đang cập nhật';

            $products[] = [
                'id' => $id,
                'name' => $item['name'] ?? 'Món mới',
                'price' => $priceText,
                'image_url' => $item['image_url'] ?? asset('images/ca_phe_nau_da.jpg'),
            ];
        }

        return $products;
    }

    private function buildMenuContext(): string
    {
        $items = SanPham::with('danhMuc')
            ->whereIn('trang_thai_ban', ['dang_ban', 'đang bán'])
            ->orderByDesc('noi_bat')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        $lines = [];
        foreach ($items as $item) {
            $price = $item->gia_khuyen_mai ?? $item->gia_goc;
            $priceText = $price !== null ? number_format((float) $price, 0, ',', '.') . 'đ' : 'Giá đang cập nhật';
            $category = $item->danhMuc?->ten_danh_muc ?? 'Menu';
            $lines[] = "- {$item->ten_san_pham} ({$category}) - {$priceText}";
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

    private function storeChatMessage(PhienChat $session, string $sender, string $content, string $type = 'văn bản'): void
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return;
        }

        TinNhanChat::create([
            'phien_chat_id' => $session->id,
            'nguoi_gui' => $sender,
            'noi_dung' => $trimmed,
            'loai_tin_nhan' => $type,
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
                return null;
            }

            $reply = $response->json('choices.0.message.content');
            return $reply ? trim((string) $reply) : null;
        } catch (\Throwable $exception) {
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
}
