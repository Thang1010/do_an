<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\DonHang;
use App\Models\KichCo;
use App\Models\SanPham;
use App\Services\OrderNotificationService;
use App\Services\PaymentService;
use App\Services\TableStatusService;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /** Thời gian pha chế ước lượng cho MỖI sản phẩm (phút). Dùng tính thời gian dự kiến hoàn thành đơn. */
    private const PHUT_MOI_SAN_PHAM = 5;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderInventoryService $inventoryService,
    ) {
    }
    // ── Helpers ──────────────────────────────────────────────────
    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session()->put('cart', $cart);
    }

    private function cartItemKey(int $productId, ?int $sizeId = null): string
    {
        return $productId . '_' . ($sizeId ?? 0);
    }

    private function uniqueCartKey(array $cart, int $productId, ?int $sizeId = null): string
    {
        $base = $this->cartItemKey($productId, $sizeId);
        do {
            $key = $base . '_' . Str::random(6);
        } while (isset($cart[$key]));

        return $key;
    }

    private function findMergeKey(array $cart, int $productId, ?int $sizeId = null, string $nhietDo = ''): ?string
    {
        foreach ($cart as $key => $item) {
            $itemSize = $item['size_id'] ?? null;
            $itemNhietDo = $item['nhiet_do'] ?? '';
            // We ignore note when merging. If they add a note later, it applies to the merged item.
            if ($item['product_id'] === $productId && $itemSize === $sizeId && $itemNhietDo === $nhietDo) {
                return $key;
            }
        }

        return null;
    }

    // ── Views ─────────────────────────────────────────────────────
    public function index()
    {
        $cart = $this->getCart();
        $items = [];
        $total = 0;

        foreach ($cart as $key => $item) {
            $product = SanPham::find($item['product_id']);
            if (!$product)
                continue;

            $sizeCode = null;
            if (!empty($item['size_id'])) {
                $kichCo = KichCo::find($item['size_id']);
                if ($kichCo) {
                    $sizeCode = $kichCo->ma_kich_co;
                }
            }

            $subtotal = $item['price'] * $item['qty'];
            $total += $subtotal;
            $items[$key] = array_merge($item, [
                'product' => $product,
                'subtotal' => $subtotal,
                'size_code' => $sizeCode,
            ]);
        }

        // Bàn đang trống
        $availableTables = BanAn::where('trang_thai', 'trống')
            ->orderBy('so_ban')
            ->get(['id', 'so_ban']);

        // Bàn gắn theo QR (nếu khách vào từ QR tại bàn) → tự điền + khoá.
        $qrTable = null;
        if (session()->has('qr_ban_an_id')) {
            $qrTable = BanAn::find(session('qr_ban_an_id'));
            if (! $qrTable || $qrTable->trang_thai === 'ngưng sử dụng') {
                session()->forget('qr_ban_an_id');
                $qrTable = null;
            }
        }

        // Danh sách đơn hàng theo ngày lọc (mặc định hôm nay) nếu đã đăng nhập
        $ordersToday = collect();
        $filterDate = request()->input('date', today()->toDateString());

        if (auth()->check()) {
            $ordersToday = DonHang::with(['chiTietDonHang', 'banAn', 'voucherNguoiDung.voucher'])
                ->where('nguoi_dung_id', auth()->id())
                ->whereDate('created_at', $filterDate)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('customer.cart.index', compact('items', 'total', 'availableTables', 'ordersToday', 'filterDate', 'qrTable'));
    }

    // ── AJAX: Thêm vào giỏ ───────────────────────────────────────
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:san_pham,id',
            'size_id' => 'nullable|integer',
            'qty' => 'nullable|integer|min:1',
            'nhiet_do' => 'nullable|string',
        ]);

        $productId = (int) $request->product_id;
        $sizeId = $request->size_id ? (int) $request->size_id : null;
        $qty = (int) ($request->qty ?? 1);
        $nhietDo = $request->nhiet_do ?? '';

        $product = SanPham::findOrFail($productId);
        $price = (float) ($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);

        // Resolve size name and size-specific price (using he_so_gia as additional amount)
        $sizeName = null;
        if ($sizeId) {
            $kichCo = $product->kichCo()->find($sizeId);
            if ($kichCo) {
                $sizeName = $kichCo->ten_kich_co;
                $price = $price * (float) ($kichCo->he_so_gia ?? 1);
            }
        }

        $cart = $this->getCart();
        $mergeKey = $this->findMergeKey($cart, $productId, $sizeId, $nhietDo);

        if ($mergeKey !== null) {
            $cart[$mergeKey]['qty'] += $qty;
        } else {
            $key = $this->cartItemKey($productId, $sizeId) . ($nhietDo ? '_' . Str::slug($nhietDo) : '');
            if (isset($cart[$key])) {
                $key = $this->uniqueCartKey($cart, $productId, $sizeId);
            }
            $cart[$key] = [
                'product_id' => $productId,
                'size_id' => $sizeId,
                'size_name' => $sizeName,
                'name' => $product->ten_san_pham,
                'image' => $product->image_url,
                'price' => $price,
                'qty' => $qty,
                'nhiet_do' => $nhietDo,
                'note' => '',
            ];
        }

        $this->saveCart($cart);

        $cartCount = array_sum(array_column($cart, 'qty'));

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng!',
            'cart_count' => $cartCount,
        ]);
    }

    // ── AJAX: Đặt hàng bằng Giọng nói (Voice Order) ───────────────
    public function voiceOrder(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,wav,mp4,mp3,ogg|max:5120',
        ]);

        $audioFile = $request->file('audio');
        $audioContent = file_get_contents($audioFile->getRealPath());

        $hfService = new \App\Services\HuggingFaceService();
        $result = $hfService->speechToText($audioContent);

        if (!$result || !isset($result['text'])) {
            return response()->json(['success' => false, 'message' => 'AI không thể nhận dạng giọng nói, vui lòng thử lại!']);
        }

        $fullText = mb_strtolower($result['text'], 'UTF-8');
        $text = $fullText; // bản dùng để dò tên món (sẽ bị thay thế dần)
        $products = SanPham::all();
        $addedItems = [];
        $cart = $this->getCart();

        // Hỗ trợ cả tiếng Việt và tiếng Anh.
        $numbers = [
            'một' => 1, 'hai' => 2, 'ba' => 3, 'bốn' => 4, 'năm' => 5, 'sáu' => 6, 'bảy' => 7, 'tám' => 8, 'chín' => 9, 'mười' => 10,
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10
        ];

        // Nhiệt độ: mặc định LẠNH, chỉ chuyển NÓNG khi khách nói rõ (Việt/Anh).
        $hotKeywords = ['nóng', 'nong', 'hot', 'ấm', 'am', 'warm'];
        $requestedNhietDo = 'lạnh';
        foreach ($hotKeywords as $kw) {
            if (mb_strpos($fullText, $kw) !== false) {
                $requestedNhietDo = 'nóng';
                break;
            }
        }

        // Ghi chú: trích các yêu cầu của khách (Việt/Anh): ít/nhiều/không đường, đá, sữa...
        $noteKeywords = [
            'không đường', 'ít đường', 'nhiều đường', 'ít ngọt', 'nhiều ngọt',
            'không đá', 'ít đá', 'nhiều đá', 'đá riêng',
            'không sữa', 'ít sữa', 'nhiều sữa', 'thêm sữa',
            'thêm trân châu', 'thêm kem', 'thêm shot', 'mang đi', 'mang về',
            'no sugar', 'less sugar', 'more sugar', 'extra sugar',
            'no ice', 'less ice', 'more ice', 'extra ice',
            'no milk', 'less milk', 'more milk', 'extra milk',
            'extra shot', 'take away', 'takeaway', 'to go',
        ];
        $foundNotes = [];
        foreach ($noteKeywords as $nk) {
            if (mb_strpos($fullText, $nk) !== false) {
                $foundNotes[] = $nk;
            }
        }
        $requestedNote = $foundNotes ? ('Khách yêu cầu: ' . implode(', ', $foundNotes)) : '';

        foreach ($products as $product) {
            $productName = mb_strtolower($product->ten_san_pham, 'UTF-8');

            if (mb_strpos($text, $productName) !== false) {
                // Thử tìm số lượng đứng trước tên món ăn
                $qty = 1;
                $pos = mb_strpos($text, $productName);
                $prefix = mb_substr($text, max(0, $pos - 20), 20);

                foreach ($numbers as $word => $num) {
                    if (mb_strpos($prefix, $word) !== false) {
                        $qty = $num;
                        break;
                    }
                }

                $productId = $product->id;
                $basePrice = (float) ($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);

                // Size: khách nói rõ thì lấy theo, không thì lấy size có hệ số giá nhỏ nhất.
                $kichCoList = $product->kichCo()->get(); // đã sắp xếp theo he_so_gia tăng dần
                $chosenSize = null;
                foreach ($kichCoList as $kc) {
                    $kcName = mb_strtolower($kc->ten_kich_co, 'UTF-8');
                    if ($kcName !== '' && mb_strpos($fullText, $kcName) !== false) {
                        $chosenSize = $kc;
                        break;
                    }
                }
                if (! $chosenSize && $kichCoList->isNotEmpty()) {
                    $chosenSize = $kichCoList->first();
                }

                $sizeId = $chosenSize?->id;
                $sizeName = $chosenSize?->ten_kich_co;
                $price = $basePrice * (float) ($chosenSize?->he_so_gia ?? 1);

                // Nhiệt độ: tôn trọng các nhiệt độ mà sản phẩm hỗ trợ.
                $productTemps = array_values(array_filter(array_map('trim', explode(',', (string) $product->nhiet_do))));
                $nhietDo = $requestedNhietDo;
                if (! empty($productTemps) && ! in_array($nhietDo, $productTemps, true)) {
                    $nhietDo = in_array('lạnh', $productTemps, true) ? 'lạnh' : $productTemps[0];
                }

                // Lưu vào giỏ hàng: CỘNG DỒN nếu đã có món giống hệt
                // (cùng sản phẩm + size + nhiệt độ + ghi chú), ngược lại tạo dòng mới.
                $mergeKey = null;
                foreach ($cart as $k => $it) {
                    if (($it['product_id'] ?? null) === $productId
                        && ($it['size_id'] ?? null) === $sizeId
                        && ($it['nhiet_do'] ?? '') === $nhietDo
                        && ($it['note'] ?? '') === $requestedNote) {
                        $mergeKey = $k;
                        break;
                    }
                }

                if ($mergeKey !== null) {
                    $cart[$mergeKey]['qty'] += $qty;
                } else {
                    $key = $this->cartItemKey($productId, $sizeId) . '_voice_' . Str::random(4);
                    $cart[$key] = [
                        'product_id' => $productId,
                        'size_id' => $sizeId,
                        'size_name' => $sizeName,
                        'name' => $product->ten_san_pham,
                        'image' => $product->image_url,
                        'price' => $price,
                        'qty' => $qty,
                        'nhiet_do' => $nhietDo,
                        'note' => $requestedNote,
                    ];
                }

                $addedItems[] = "{$qty} {$product->ten_san_pham}";

                // Tránh trùng lặp nếu tên món có nhiều từ giống nhau (vd: Cà phê đen, Cà phê)
                $text = str_replace($productName, '***', $text);
            }
        }

        if (count($addedItems) > 0) {
            $this->saveCart($cart);
            $cartCount = array_sum(array_column($cart, 'qty'));
            return response()->json([
                'success' => true, 
                'text' => trim($result['text']),
                'message' => 'AI nghe được: "' . trim($result['text']) . '"<br>Đã thêm: <b>' . implode(', ', $addedItems) . '</b>',
                'cart_count' => $cartCount
            ]);
        }

        return response()->json([
            'success' => false, 
            'text' => trim($result['text']),
            'message' => 'AI nghe được: "' . trim($result['text']) . '"<br>Nhưng không tìm thấy món nào trong menu khớp với yêu cầu!'
        ]);
    }

    // ── AJAX: Cập nhật số lượng ───────────────────────────────────
    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'qty' => 'required|integer|min:0',
        ]);

        $cart = $this->getCart();
        $key = $request->key;

        if ($request->qty <= 0) {
            unset($cart[$key]);
        } elseif (isset($cart[$key])) {
            $cart[$key]['qty'] = $request->qty;
        }

        $this->saveCart($cart);

        $total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
        $cartCount = array_sum(array_column($cart, 'qty'));

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'total' => $total,
        ]);
    }

    // ── AJAX: Cập nhật ghi chú ───────────────────────────────
    public function updateNote(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'note' => 'nullable|string|max:255',
        ]);

        $cart = $this->getCart();
        $key = $request->key;

        if (isset($cart[$key])) {
            $cart[$key]['note'] = trim((string) $request->note);
        }

        $this->saveCart($cart);

        return response()->json(['success' => true]);
    }

    // ── AJAX: Xóa 1 item ─────────────────────────────────────────
    public function remove(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        $cart = $this->getCart();
        unset($cart[$request->key]);
        $this->saveCart($cart);

        $total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
        $cartCount = array_sum(array_column($cart, 'qty'));

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'total' => $total,
        ]);
    }

    // ── AJAX: Lấy số lượng giỏ hàng ──────────────────────────────
    public function count()
    {
        $cart = $this->getCart();
        $cartCount = array_sum(array_column($cart, 'qty'));
        return response()->json(['cart_count' => $cartCount]);
    }

    // ── Checkout (POST) ───────────────────────────────────────────
    public function checkout(Request $request)
    {
        $cart = $this->getCart();

        if (empty($cart)) {
            return back()->with('error', 'Giỏ hàng trống.');
        }

        // Vào từ QR tại bàn → ép "gọi món" tại đúng bàn đó, khoá bàn (1 bàn = 1 QR),
        // bỏ qua mọi lựa chọn bàn/hình thức gửi từ client.
        if (session()->has('qr_ban_an_id')) {
            $qrBan = BanAn::find(session('qr_ban_an_id'));
            if ($qrBan && $qrBan->trang_thai !== 'ngưng sử dụng') {
                $request->merge([
                    'loai_don_hidden' => 'goi_mon',
                    'loai_don' => null,
                    'ban_an_id_goi_mon' => $qrBan->id,
                    'phuong_thuc_thanh_toan_goi_mon' => 'chuyển khoản',
                ]);
            } else {
                session()->forget('qr_ban_an_id');
            }
        }

        $loaiDonHidden = $request->input('loai_don_hidden');
        $loaiDonLegacy = $request->input('loai_don');
        $loaiDonUi = $loaiDonHidden;
        if (!$loaiDonUi && $loaiDonLegacy) {
            $loaiDonUi = $loaiDonLegacy === 'dat_hang' ? 'goi_mon' : 'dat_ban';
        }

        // ── Khách vãng lai ───────────────────────────────────────
        if (!auth()->check()) {
            if ($loaiDonUi === 'dat_ban') {
                return back()->with('error', 'Bạn cần đăng nhập để đặt bàn trước.');
            }

            // Mang về: không cần bàn, chỉ cần email để nhận hoá đơn.
            if ($loaiDonUi === 'mang_ve') {
                $request->validate([
                    'email_khach_hang' => 'required|email|max:255',
                ]);

                $order = $this->createOrder([
                    'nguoi_dung_id' => null,
                    'email_khach_hang' => $request->email_khach_hang,
                    'loai_don' => 'mang về',
                    'ban_an_id' => null,
                    'trang_thai_thanh_toan' => 'chưa thanh toán',
                    'phuong_thuc_thanh_toan' => 'chuyển khoản',
                ], $cart, null);

                // Giữ giỏ hàng & QR tới khi thanh toán THÀNH CÔNG (xóa ở luồng success). Khách hủy → không mất giỏ.
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'order_code' => $order->ma_don_hang,
                        'order_id' => $order->id
                    ]);
                }
                return redirect()->route('cart.payment', $order->ma_don_hang);
            }

            $request->validate([
                'ban_an_id_goi_mon' => 'required|exists:ban_an,id',
                'email_khach_hang' => 'required|email|max:255',
            ]);

            $order = $this->createOrder([
                'nguoi_dung_id' => null,
                'email_khach_hang' => $request->email_khach_hang,
                'loai_don' => 'sử dụng ngay',
                'ban_an_id' => $request->ban_an_id_goi_mon,
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => 'chuyển khoản',
            ], $cart, null);

            // Giữ giỏ hàng & QR tới khi thanh toán THÀNH CÔNG (xóa ở luồng success). Khách hủy → không mất giỏ.
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'order_code' => $order->ma_don_hang,
                    'order_id' => $order->id
                ]);
            }
            return redirect()->route('cart.payment', $order->ma_don_hang);
        }

        // ── Khách ĐÃ đăng nhập ───────────────────────────────────
        if (!$request->filled('orders')) {
            $request->validate([
                'loai_don_hidden' => 'nullable|in:dat_ban,goi_mon,mang_ve|required_without:loai_don',
                'loai_don' => 'nullable|in:dat_ban,dat_hang|required_without:loai_don_hidden',
                'email_khach' => 'nullable|email|max:255',
            ]);

            if (empty(auth()->user()->email) && $request->filled('email_khach')) {
                auth()->user()->update(['email' => $request->email_khach]);
            }

            if ($loaiDonUi === 'dat_ban') {
                $request->validate([
                    'thoi_gian_den' => 'required|date_format:H:i',
                    'ban_an_id_dat_ban' => 'nullable|exists:ban_an,id',
                ]);

                $thoiGianDen = \Carbon\Carbon::createFromFormat('H:i', $request->thoi_gian_den)->setDateFrom(now());
                if ($thoiGianDen->isPast()) {
                    return back()->with('error', 'Thời gian đến phải lớn hơn thời gian hiện tại.');
                }

                $banId = $request->ban_an_id_dat_ban;
                $loaiDon = 'đặt hàng trước';

                $voucherId = null;
                if ($request->filled('voucher_nguoi_dung_id')) {
                    $ids = explode(',', $request->voucher_nguoi_dung_id);
                    $voucherId = (int) $ids[0];
                }

                $order = $this->createOrder([
                    'nguoi_dung_id' => auth()->id(),
                    'loai_don' => $loaiDon,
                    'ban_an_id' => $banId,
                    'thoi_gian_den' => $thoiGianDen->format('Y-m-d H:i:s'),
                    'trang_thai_thanh_toan' => 'chưa thanh toán',
                    'phuong_thuc_thanh_toan' => 'chuyển khoản',
                ], $cart, $voucherId);

                // Giữ giỏ hàng & QR tới khi thanh toán THÀNH CÔNG (xóa ở luồng success). Khách hủy → không mất giỏ.
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'order_code' => $order->ma_don_hang,
                        'order_id' => $order->id
                    ]);
                }
                return redirect()->route('cart.payment', $order->ma_don_hang);
            }

            // Mang về: không cần bàn, thanh toán trước qua chuyển khoản.
            if ($loaiDonUi === 'mang_ve') {
                $voucherId = null;
                if ($request->filled('voucher_nguoi_dung_id')) {
                    $ids = explode(',', $request->voucher_nguoi_dung_id);
                    $voucherId = (int) $ids[0];
                }

                $order = $this->createOrder([
                    'nguoi_dung_id' => auth()->id(),
                    'loai_don' => 'mang về',
                    'ban_an_id' => null,
                    'trang_thai_thanh_toan' => 'chưa thanh toán',
                    'phuong_thuc_thanh_toan' => 'chuyển khoản',
                ], $cart, $voucherId);

                // Giữ giỏ hàng & QR tới khi thanh toán THÀNH CÔNG (xóa ở luồng success). Khách hủy → không mất giỏ.
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'order_code' => $order->ma_don_hang,
                        'order_id' => $order->id
                    ]);
                }
                return redirect()->route('cart.payment', $order->ma_don_hang);
            }

            $request->validate([
                'ban_an_id_goi_mon' => 'required|exists:ban_an,id',
                'phuong_thuc_thanh_toan_goi_mon' => 'required|string|in:chuyển khoản',
            ]);

            $voucherId = null;
            if ($request->filled('voucher_nguoi_dung_id')) {
                $ids = explode(',', $request->voucher_nguoi_dung_id);
                $voucherId = (int) $ids[0];
            }

            $order = $this->createOrder([
                'nguoi_dung_id' => auth()->id(),
                'loai_don' => 'sử dụng ngay',
                'ban_an_id' => $request->ban_an_id_goi_mon,
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => 'chuyển khoản',
            ], $cart, $voucherId);

            // Giữ giỏ hàng & QR tới khi thanh toán THÀNH CÔNG (xóa ở luồng success). Khách hủy → không mất giỏ.
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'order_code' => $order->ma_don_hang,
                    'order_id' => $order->id
                ]);
            }
            return redirect()->route('cart.payment', $order->ma_don_hang);
        }

        // Có thể tạo nhiều đơn cùng lúc theo danh sách bàn được chọn
        return back()->with('error', 'Vui lòng thanh toán từng đơn để hoàn tất đặt hàng.');
    }

    // ── Trang success ─────────────────────────────────────────────
    public function success()
    {
        // Tới được trang xác nhận = đã thanh toán xong → dọn giỏ hàng (an toàn, nếu còn).
        session()->forget(['cart', 'qr_ban_an_id']);

        $orderCode = session('order_code') ?: request()->query('order_code');
        $orderCodes = session('order_codes', []);

        $orders = collect();
        if ($orderCode) {
            $order = DonHang::with(['banAn', 'chiTietDonHang'])
                ->where('ma_don_hang', $orderCode)
                ->first();
            if ($order) {
                $orders->push($order);
            }
        }
        if (!empty($orderCodes)) {
            $dbOrders = DonHang::with(['banAn', 'chiTietDonHang'])
                ->whereIn('ma_don_hang', $orderCodes)
                ->get();
            $orders = $orders->merge($dbOrders);
        }

        return view('customer.cart.success', compact('orderCode', 'orderCodes', 'orders'));
    }

    public function payment(string $orderCode)
    {
        $order = DonHang::with(['chiTietDonHang', 'nguoiDung'])
            ->where('ma_don_hang', $orderCode)
            ->firstOrFail();

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return redirect()->route('cart.success')->with('order_code', $order->ma_don_hang);
        }

        if (request()->has('email')) {
            $order->update(['email_khach_hang' => request()->input('email')]);
        }

        if ($order->nguoi_dung_id && (!auth()->check() || (auth()->id() !== $order->nguoi_dung_id && !in_array(auth()->user()->vai_tro, ['nhân viên', 'quản lý', 'chủ cửa hàng'])))) {
            abort(403);
        }

        // Đơn KHÁCH HÀNG: kiểm tra tồn kho TRƯỚC khi tạo link thanh toán.
        // Nếu trong lúc khách chần chừ mà nguyên liệu đã hết (người khác mua trước)
        // → báo "đã hết hàng" và không cho thanh toán. Đơn cửa hàng đã trừ kho từ
        // lúc tạm tính nên bỏ qua check này.
        if (is_null($order->nhan_vien_id)) {
            $shortages = $this->inventoryService->checkStockForOrder($order);
            if (!empty($shortages)) {
                $message = 'Rất tiếc, sản phẩm đã hết hàng: ' . implode('; ', $shortages);
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message]);
                }
                return back()->with('error', $message);
            }
        }

        $clientId = env('PAYOS_CLIENT_ID');
        $apiKey = env('PAYOS_API_KEY');
        $checksumKey = env('PAYOS_CHECKSUM_KEY');

        if (!$clientId || !$apiKey || !$checksumKey) {
            return back()->with('error', 'Chưa cấu hình PayOS. Vui lòng liên hệ quản trị viên.');
        }

        $payOS = new \PayOS\PayOS($clientId, $apiKey, $checksumKey);

        $items = [];
        foreach ($order->chiTietDonHang as $ct) {
            $items[] = [
                "name" => substr($ct->ten_san_pham, 0, 255),
                "quantity" => (int) $ct->so_luong,
                "price" => (int) $ct->don_gia,
            ];
        }

        $orderCodeInt = intval($order->id . time());
        // Lấy 15 ký tự để tránh vượt quá limit (tuỳ chỉnh an toàn)
        $orderCodeInt = (int) substr((string) $orderCodeInt, 0, 15);

        $source = request()->query('source');
        $data = [
            "orderCode" => $orderCodeInt,
            "amount" => (int) $order->tong_tien,
            "description" => substr("TT don " . $orderCode, 0, 25),
            "items" => $items,
            "returnUrl" => route('cart.payos.return', ['orderCode' => $orderCode, 'source' => $source]),
            "cancelUrl" => route('cart.payos.cancel', ['orderCode' => $orderCode, 'source' => $source]),
        ];

        try {
            // Bypass SSL error using Http client without verification
            $signature = \PayOS\Utils\PayOSSignatureUtils::createSignatureOfPaymentRequest($checksumKey, $data);
            $data['signature'] = $signature;

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'x-client-id' => $clientId,
                    'x-api-key' => $apiKey,
                ])
                ->post('https://api-merchant.payos.vn/v2/payment-requests', $data);

            if ($response->failed() || $response->json('code') !== '00') {
                throw new \Exception($response->json('desc') ?? 'Lỗi kết nối PayOS');
            }

            $checkoutUrl = $response->json('data.checkoutUrl');
            $payosOrderId = $orderCodeInt;

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'checkoutUrl' => $checkoutUrl,
                    'payosOrderId' => $payosOrderId,
                    'returnUrl' => $data['returnUrl'],
                ]);
            }

            return redirect($checkoutUrl);
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi tạo link thanh toán PayOS: ' . $e->getMessage()
                ]);
            }
            return back()->with('error', 'Lỗi tạo link thanh toán PayOS: ' . $e->getMessage());
        }
    }

    public function paymentStatusAjax(Request $request, string $orderCode)
    {
        $order = DonHang::where('ma_don_hang', $orderCode)->firstOrFail();
        
        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            session()->forget(['cart', 'qr_ban_an_id']);
            return response()->json(['success' => true, 'status' => 'đã thanh toán']);
        }

        $payosOrderId = $request->query('payosOrderId');
        if (!$payosOrderId) {
            return response()->json(['success' => false, 'status' => $order->trang_thai_thanh_toan]);
        }

        $clientId = config('services.payos.client_id', env('PAYOS_CLIENT_ID'));
        $apiKey = config('services.payos.api_key', env('PAYOS_API_KEY'));
        $checksumKey = config('services.payos.checksum_key', env('PAYOS_CHECKSUM_KEY'));

        if ($clientId && $apiKey && $checksumKey) {
            try {
                // Thay vì dùng SDK có thể bị lỗi SSL nội bộ XAMPP, dùng Http::withoutVerifying() giống như lúc tạo link
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders([
                        'x-client-id' => $clientId,
                        'x-api-key' => $apiKey,
                    ])
                    ->get("https://api-merchant.payos.vn/v2/payment-requests/{$payosOrderId}");

                if ($response->successful() && $response->json('code') === '00') {
                    $status = $response->json('data.status');
                    if ($status === 'PAID') {
                        $order->updatePaymentStatus('đã thanh toán', 'chuyển khoản');
                        $this->exportStockForPaidCustomerOrder($order);
                        $this->paymentService->syncThanhToanRecord($order, 'chuyển khoản', 'đã thanh toán');
                        $this->paymentService->applyTableStatusAfterPayment($order->fresh());

                        // Gửi email xác nhận cho KHÁCH (kèm thời gian đặt & dự kiến hoàn thành).
                        // Nhánh này chỉ chạy 1 lần (lần đầu đơn chuyển sang "đã thanh toán") nên không gửi trùng.
                        if ($order->nguoiDung && $order->nguoiDung->email) {
                            \Illuminate\Support\Facades\Mail::to($order->nguoiDung->email)->send(new \App\Mail\CustomerOrderPaidMail($order));
                        } elseif ($order->email_khach_hang) {
                            \Illuminate\Support\Facades\Mail::to($order->email_khach_hang)->send(new \App\Mail\CustomerOrderPaidMail($order));
                        }

                        session()->forget(['cart', 'qr_ban_an_id']);
                        return response()->json(['success' => true, 'status' => 'đã thanh toán']);
                    }
                    // Khách bấm "Hủy" trên PayOS → link bị huỷ/hết hạn → báo client đóng modal.
                    if (in_array($status, ['CANCELLED', 'EXPIRED'], true)) {
                        return response()->json(['success' => true, 'status' => 'đã hủy']);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('PayOS Polling Error: ' . $e->getMessage());
                // Ignore exception, continue polling
            }
        }

        return response()->json(['success' => true, 'status' => $order->trang_thai_thanh_toan]);
    }

    public function payosReturn(Request $request, string $orderCode)
    {
        $order = DonHang::where('ma_don_hang', $orderCode)->firstOrFail();
        $source = $request->query('source');

        if ($request->code === '00' && $order->trang_thai_thanh_toan !== 'đã thanh toán') {
            $order->updatePaymentStatus('đã thanh toán', 'chuyển khoản');
            $this->exportStockForPaidCustomerOrder($order);
            $this->paymentService->syncThanhToanRecord($order, 'chuyển khoản', 'đã thanh toán');
            $this->paymentService->applyTableStatusAfterPayment($order->fresh());
        }

        // Quay về từ PayOS (thành công) → xóa giỏ hàng.
        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            session()->forget(['cart', 'qr_ban_an_id']);
        }

        if ($source === 'staff' && $order->ban_an_id) {
            return redirect()->route('staff.tables.index', ['table' => $order->ban_an_id])->with('success', 'Đã thanh toán thành công qua PayOS.');
        } elseif ($source === 'manager' && $order->ban_an_id) {
            return redirect()->route('manager.tables.index', ['table' => $order->ban_an_id])->with('success', 'Đã thanh toán thành công qua PayOS.');
        }

        return redirect()->route('cart.success')->with('order_code', $orderCode);
    }

    public function payosCancel(Request $request, string $orderCode)
    {
        $order = DonHang::where('ma_don_hang', $orderCode)->firstOrFail();
        $source = $request->query('source');

        if ($source === 'staff' && $order->ban_an_id) {
            $fallbackUrl = route('staff.tables.index', ['table' => $order->ban_an_id]);
        } elseif ($source === 'manager' && $order->ban_an_id) {
            $fallbackUrl = route('manager.tables.index', ['table' => $order->ban_an_id]);
        } else {
            $fallbackUrl = route('menu.index');
        }

        // Trang này bị PayOS nạp trong iframe khi khách HỦY. Nếu đang trong iframe (modal)
        // → báo trang cha đóng modal; nếu mở full-page → điều hướng về fallbackUrl như cũ.
        return response()->view('customer.cart.payos-cancel', [
            'orderCode' => $orderCode,
            'fallbackUrl' => $fallbackUrl,
        ]);
    }

    public function cancelGuest(Request $request)
    {
        $request->validate(['order_code' => 'required|string']);

        $order = DonHang::where('ma_don_hang', $request->order_code)->firstOrFail();

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return back()->with('error', 'Đơn hàng đã thanh toán, không thể hủy.');
        }

        if ($order->nguoi_dung_id && (!auth()->check() || auth()->id() !== $order->nguoi_dung_id)) {
            abort(403);
        }

        $banAnId = $order->ban_an_id;
        $order->delete();

        if ($banAnId) {
            TableStatusService::refreshForTable($banAnId);
        }

        return redirect()->route('menu.index')->with('success', 'Đã hủy đơn hàng.');
    }

    /**
     * Khách HỦY/ĐÓNG modal thanh toán mà chưa trả tiền → xóa đơn chưa thanh toán
     * (hoàn voucher), NHƯNG GIỮ NGUYÊN giỏ hàng để khách đặt lại.
     *
     * Đơn khách CHƯA trừ kho (chỉ trừ khi thanh toán thành công) nên KHÔNG hoàn kho
     * ở đây — hoàn sẽ làm tồn kho bị cộng khống.
     */
    public function abandonPayment(Request $request, string $orderCode)
    {
        $order = DonHang::where('ma_don_hang', $orderCode)->first();
        if (!$order) {
            return response()->json(['success' => true]); // đã bị xóa rồi
        }

        // Chỉ chủ đơn (thành viên) hoặc đơn khách vãng lai mới được hủy.
        if ($order->nguoi_dung_id && (!auth()->check() || auth()->id() !== $order->nguoi_dung_id)) {
            abort(403);
        }

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return response()->json(['success' => false, 'message' => 'Đơn đã thanh toán.'], 422);
        }

        $banAnId = $order->ban_an_id;
        DB::transaction(function () use ($order) {
            // Chỉ hoàn kho cho đơn do CỬA HÀNG tạo (đã trừ kho từ trước). Đơn khách
            // chưa trừ kho khi chưa thanh toán nên không hoàn (tránh cộng khống).
            if (!is_null($order->nhan_vien_id)) {
                $this->inventoryService->restoreIngredientsForOrder($order);
            }
            \App\Models\ThanhToan::where('don_hang_id', $order->id)->delete();
            $order->chiTietDonHang()->delete();
            $order->delete(); // booted() của DonHang tự hoàn voucher
        });

        if ($banAnId) {
            TableStatusService::refreshForTable($banAnId);
        }

        return response()->json(['success' => true]);
    }

    public function confirmPayment(Request $request, string $orderCode)
    {
        $request->validate([
            'order_code' => 'required|string',
        ]);

        if ($request->order_code !== $orderCode) {
            return back()->with('error', 'Mã đơn hàng không khớp.');
        }

        $order = DonHang::where('ma_don_hang', $orderCode)->firstOrFail();

        if ($order->trang_thai_thanh_toan !== 'đã thanh toán') {
            $order->updatePaymentStatus('đã thanh toán', 'chuyển khoản');
            $this->exportStockForPaidCustomerOrder($order);
            $this->paymentService->syncThanhToanRecord($order, 'chuyển khoản', 'đã thanh toán');
            $this->paymentService->applyTableStatusAfterPayment($order->fresh());

            if ($order->nguoiDung && $order->nguoiDung->email) {
                \Illuminate\Support\Facades\Mail::to($order->nguoiDung->email)->send(new \App\Mail\CustomerOrderPaidMail($order));
            } elseif ($order->email_khach_hang) {
                \Illuminate\Support\Facades\Mail::to($order->email_khach_hang)->send(new \App\Mail\CustomerOrderPaidMail($order));
            }
        }

        session()->forget(['cart', 'qr_ban_an_id']);

        // Khách hàng thành viên (chủ đơn) → trang chi tiết đơn; khách vãng lai → trang xác nhận.
        if ($order->nguoi_dung_id && auth()->check() && auth()->id() === $order->nguoi_dung_id) {
            return redirect()->route('customer.orders.show', $order->id);
        }

        return redirect()->route('cart.success')->with('order_code', $order->ma_don_hang);
    }

    // ── Internal helper ───────────────────────────────────────────
    private function createOrder(array $attrs, array $cart, ?int $voucherNguoiDungId = null): DonHang
    {
        return DB::transaction(function () use ($attrs, $cart, $voucherNguoiDungId) {
            $tamTinh = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
            $soTienGiam = 0;

            if ($voucherNguoiDungId) {
                $vu = \App\Models\VoucherNguoiDung::with('voucher')->find($voucherNguoiDungId);
                if ($vu && $vu->trang_thai === 'chưa dùng' && $vu->voucher) {
                    $v = $vu->voucher;
                    // 'ngừng phát hành' chi dung phat them, tai khoan da nhan van dung duoc.
                    $voucherUsable = in_array($v->trang_thai, ['đang hoạt động', 'ngừng phát hành'], true);
                    if ($voucherUsable && now()->between($v->ngay_bat_dau, $v->ngay_ket_thuc)) {
                        if ($tamTinh >= $v->don_toi_thieu) {
                            if ($v->loai_giam === 'phần trăm') {
                                $soTienGiam = $tamTinh * ($v->gia_tri_giam / 100);
                                if ($v->giam_toi_da) {
                                    $soTienGiam = min($soTienGiam, $v->giam_toi_da);
                                }
                            } else {
                                $soTienGiam = $v->gia_tri_giam;
                            }
                            $soTienGiam = min($soTienGiam, $tamTinh);
                            $vu->update(['trang_thai' => 'đã dùng', 'da_dung_luc' => now()]);
                        } else {
                            $voucherNguoiDungId = null;
                        }
                    } else {
                        $voucherNguoiDungId = null;
                    }
                } else {
                    $voucherNguoiDungId = null;
                }
            }

            // Extract columns that belong to don_hang only
            $loaiDon = $attrs['loai_don'] ?? 'đặt hàng trước';
            $trangThaiThanhToan = $attrs['trang_thai_thanh_toan'] ?? 'chưa thanh toán';
            $phuongThucThanhToan = $attrs['phuong_thuc_thanh_toan'] ?? null;

            // Thời gian đến / dự kiến hoàn thành:
            //  - 'đặt hàng trước': giờ khách tự chọn (đã truyền sẵn trong $attrs['thoi_gian_den']).
            //  - 'mang về' / 'sử dụng ngay': tính từ thời điểm đặt + (tổng số sản phẩm × 5 phút/sản phẩm).
            $thoiGianDen = $attrs['thoi_gian_den'] ?? null;
            if ($thoiGianDen === null && in_array($loaiDon, ['mang về', 'sử dụng ngay'], true)) {
                $tongSoLuong = array_sum(array_map(fn($i) => $i['qty'], $cart));
                $thoiGianDen = now()->addMinutes(self::PHUT_MOI_SAN_PHAM * $tongSoLuong);
            }

            $order = DonHang::create([
                'ma_don_hang' => 'ORD-' . strtoupper(Str::random(8)),
                'voucher_nguoi_dung_id' => $voucherNguoiDungId,
                'nguoi_dung_id' => $attrs['nguoi_dung_id'] ?? null,
                'email_khach_hang' => $attrs['email_khach_hang'] ?? null,
                'nhan_vien_id' => $attrs['nhan_vien_id'] ?? null,
                'ban_an_id' => $attrs['ban_an_id'] ?? null,
            ]);

            foreach ($cart as $item) {
                $thanhTien = $item['price'] * $item['qty'];
                $itemDiscount = $tamTinh > 0 ? round($soTienGiam * $thanhTien / $tamTinh, 2) : 0;
                $tongTienItem = max(0, $thanhTien - $itemDiscount);

                ChiTietDonHang::create([
                    'don_hang_id' => $order->id,
                    'san_pham_id' => $item['product_id'],
                    'kich_co_id' => $item['size_id'] ?? null,
                    'ten_san_pham' => $item['name'],
                    'ten_kich_co' => $item['size_name'] ?? null,
                    'don_gia' => $item['price'],
                    'so_luong' => $item['qty'],
                    'thanh_tien' => $thanhTien,
                    'ghi_chu_mon' => trim(($item['nhiet_do'] ? "({$item['nhiet_do']}) " : '') . ($item['note'] ?? '')),
                    'loai_don' => $loaiDon,
                    'thoi_gian_den' => $thoiGianDen,
                    'trang_thai_thanh_toan' => $trangThaiThanhToan,
                    'phuong_thuc_thanh_toan' => $phuongThucThanhToan,
                    'so_tien_giam' => $itemDiscount,
                    'tong_tien' => $tongTienItem,
                ]);
            }

            // KHÔNG trừ kho ở đây. Đơn khách chỉ trừ kho khi THANH TOÁN THÀNH CÔNG
            // (xem exportStockForPaidCustomerOrder()). Đơn do cửa hàng tạo đã trừ kho
            // ở luồng tạm tính/thêm món riêng.

            return $order;
        });
    }

    /**
     * Trừ kho cho đơn KHÁCH HÀNG khi thanh toán thành công.
     *
     * CHỈ áp dụng cho đơn khách (nhan_vien_id === null). Đơn do cửa hàng tạo
     * (nhân viên/quản lý/chủ) đã trừ kho lúc tạm tính/thêm món nên KHÔNG trừ lại
     * ở đây để tránh trừ kho 2 lần (payosReturn/paymentStatusAjax dùng chung cho
     * cả khách lẫn cửa hàng qua source=staff/manager).
     */
    private function exportStockForPaidCustomerOrder(DonHang $order): void
    {
        if (!is_null($order->nhan_vien_id)) {
            return;
        }

        $this->inventoryService->exportIngredientsForPaidOrder($order);
    }
}
