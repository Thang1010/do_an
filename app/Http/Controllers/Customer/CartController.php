<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Services\OrderNotificationService;
use App\Services\PaymentService;
use App\Services\TableStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}
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

    private function findMergeKey(array $cart, int $productId, ?int $sizeId = null): ?string
    {
        foreach ($cart as $key => $item) {
            $itemSize = $item['size_id'] ?? null;
            $itemNote = trim((string) ($item['note'] ?? ''));
            if ($item['product_id'] === $productId && $itemSize === $sizeId && $itemNote === '') {
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

            $subtotal = $item['price'] * $item['qty'];
            $total += $subtotal;
            $items[$key] = array_merge($item, [
                'product' => $product,
                'subtotal' => $subtotal,
            ]);
        }

        // Bàn đang trống
        $availableTables = BanAn::where('trang_thai', 'trống')
            ->orderBy('so_ban')
            ->get(['id', 'so_ban']);

        // Danh sách đơn hàng theo ngày lọc (mặc định hôm nay) nếu đã đăng nhập
        $ordersToday = collect();
        $filterDate = request()->input('date', today()->toDateString());
        
        if (auth()->check()) {
            $ordersToday = DonHang::with(['chiTietDonHang', 'banAn', 'voucherNguoiDung.voucher'])
                ->where('nguoi_dung_id', auth()->id())
                ->whereDate('created_at', $filterDate)
                ->orderByRaw("CASE WHEN trang_thai_don = 'chờ xác nhận' THEN 0 ELSE 1 END")
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('customer.cart.index', compact('items', 'total', 'availableTables', 'ordersToday', 'filterDate'));
    }

    // ── AJAX: Thêm vào giỏ ───────────────────────────────────────
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:san_pham,id',
            'size_id' => 'nullable|integer',
            'qty' => 'nullable|integer|min:1',
        ]);

        $productId = (int) $request->product_id;
        $sizeId = $request->size_id ? (int) $request->size_id : null;
        $qty = (int) ($request->qty ?? 1);

        $product = SanPham::findOrFail($productId);
        $price = (float) ($product->gia_khuyen_mai ?? $product->gia_goc);

        $cart = $this->getCart();
        $mergeKey = $this->findMergeKey($cart, $productId, $sizeId);

        if ($mergeKey !== null) {
            $cart[$mergeKey]['qty'] += $qty;
        } else {
            $key = $this->cartItemKey($productId, $sizeId);
            if (isset($cart[$key])) {
                $key = $this->uniqueCartKey($cart, $productId, $sizeId);
            }
            $cart[$key] = [
                'product_id' => $productId,
                'size_id' => $sizeId,
                'name' => $product->ten_san_pham,
                'image' => $product->image_url,
                'price' => $price,
                'qty' => $qty,
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

        $loaiDonHidden = $request->input('loai_don_hidden');
        $loaiDonLegacy = $request->input('loai_don');
        $loaiDonUi = $loaiDonHidden;
        if (!$loaiDonUi && $loaiDonLegacy) {
            $loaiDonUi = $loaiDonLegacy === 'dat_hang' ? 'goi_mon' : 'dat_ban';
        }

        // ── Khách CHƯA đăng nhập ─────────────────────────────────
        if (!auth()->check()) {
            $request->validate([
                'ten_khach_hang' => 'required|string|max:100',
                'so_dien_thoai_khach' => 'required|string|max:20',
                'loai_don_hidden' => 'required|in:goi_mon',
            ]);

            if ($loaiDonUi !== 'goi_mon') {
                return back()->with('error', 'Khách vãng lai chỉ được gọi món tại bàn.');
            }

            // Gọi món tại bàn (Đã ở quán)
            $request->validate([
                'ban_an_id_goi_mon' => 'required|exists:ban_an,id',
                'phuong_thuc_thanh_toan_goi_mon' => 'required|string|in:chuyển khoản',
            ]);

            $order = $this->createOrder([
                'loai_don' => 'mua tại quán',
                'ban_an_id' => $request->ban_an_id_goi_mon,
                'trang_thai_don' => 'chờ xác nhận',
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => 'chuyển khoản',
                'ten_khach_hang' => $request->ten_khach_hang,
                'so_dien_thoai_khach' => $request->so_dien_thoai_khach,
            ], $cart);

            session()->forget('cart');
            return redirect()->route('cart.payment', $order->ma_don_hang);
        }

        // ── Khách ĐÃ đăng nhập ───────────────────────────────────
        // Hỗ trợ 2 mode: đặt 1 đơn từ giỏ hoặc multi-order theo danh sách bàn
        if (!$request->filled('orders')) {
            $request->validate([
                'loai_don_hidden' => 'nullable|in:dat_ban,goi_mon|required_without:loai_don',
                'loai_don' => 'nullable|in:dat_ban,dat_hang|required_without:loai_don_hidden',
                'so_dien_thoai_khach' => 'nullable|string|max:20',
            ]);

            if ($loaiDonUi === 'dat_ban') {
                $request->validate([
                    'thoi_gian_den' => 'required|date',
                    'ban_an_id_dat_ban' => 'nullable|exists:ban_an,id',
                ]);

                $ghiChu = 'Hẹn đến lúc: ' . date('H:i d/m/Y', strtotime($request->thoi_gian_den));
                $banId = $request->ban_an_id_dat_ban;
                $loaiDon = $banId ? 'mua tại quán' : 'đặt online';

                $order = $this->createOrder([
                    'nguoi_dung_id' => auth()->id(),
                    'loai_don' => $loaiDon,
                    'ban_an_id' => $banId,
                    'trang_thai_don' => 'chờ xác nhận',
                    'trang_thai_thanh_toan' => 'chưa thanh toán',
                    'phuong_thuc_thanh_toan' => 'chuyển khoản',
                    'ghi_chu' => $ghiChu,
                    'so_dien_thoai_khach' => $request->so_dien_thoai_khach,
                ], $cart);

                session()->forget('cart');
                return redirect()->route('cart.payment', $order->ma_don_hang);
            }

            $request->validate([
                'ban_an_id_goi_mon' => 'required|exists:ban_an,id',
                'phuong_thuc_thanh_toan_goi_mon' => 'required|string|in:tiền mặt,chuyển khoản',
            ]);

            $order = $this->createOrder([
                'nguoi_dung_id' => auth()->id(),
                'loai_don' => 'mua tại quán',
                'ban_an_id' => $request->ban_an_id_goi_mon,
                'trang_thai_don' => 'chờ xác nhận',
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => $request->phuong_thuc_thanh_toan_goi_mon,
                'so_dien_thoai_khach' => $request->so_dien_thoai_khach,
            ], $cart);

            session()->forget('cart');
            return redirect()->route('cart.success')->with('order_code', $order->ma_don_hang);
        }

        // Có thể tạo nhiều đơn cùng lúc theo danh sách bàn được chọn
        $request->validate([
            'orders' => 'required|array|min:1',
            'orders.*.ban_an_id' => 'nullable|exists:ban_an,id',
            'orders.*.thoi_gian_den' => 'nullable|date',
            'orders.*.keys' => 'required|array|min:1',   // các key item trong giỏ
        ]);

        $createdOrders = [];

        foreach ($request->orders as $orderData) {
            $selectedKeys = $orderData['keys'];
            $subCart = array_intersect_key($cart, array_flip($selectedKeys));

            if (empty($subCart))
                continue;

            $banId = $orderData['ban_an_id'] ?? null;
            $loaiDon = $banId ? 'mua tại quán' : 'đặt online';

            $ghiChu = null;
            if (!empty($orderData['thoi_gian_den'])) {
                $ghiChu = 'Hẹn đến lúc: ' . date('H:i d/m/Y', strtotime($orderData['thoi_gian_den']));
            }

            $order = $this->createOrder([
                'nguoi_dung_id' => auth()->id(),
                'loai_don' => $loaiDon,
                'ban_an_id' => $banId,
                'trang_thai_don' => 'chờ xác nhận',
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => null,
                'ghi_chu' => $ghiChu,
            ], $subCart);

            $createdOrders[] = $order->ma_don_hang;
        }

        session()->forget('cart');

        return redirect()->route('cart.success')->with('order_codes', $createdOrders);
    }

    // ── Trang success ─────────────────────────────────────────────
    public function success()
    {
        $orderCode = session('order_code');
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

        if ($order->trang_thai_don === 'đã hủy') {
            return redirect()->route('menu.index')->with('error', 'Đơn hàng đã bị hủy.');
        }

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return redirect()->route('cart.success')->with('order_code', $order->ma_don_hang);
        }

        if ($order->nguoi_dung_id && (!auth()->check() || auth()->id() !== $order->nguoi_dung_id)) {
            abort(403);
        }

        $store = $this->paymentService->resolveStoreForPayment($order->nguoiDung?->cua_hang_id);
        $qrData = $store ? $this->paymentService->generateQrData($order, $store) : null;

        return view('customer.cart.payment', compact('order', 'qrData', 'store'));
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

        if ($order->trang_thai_don === 'đã hủy') {
            return redirect()->route('menu.index')->with('error', 'Đơn hàng đã bị hủy.');
        }

        if ($order->trang_thai_thanh_toan !== 'đã thanh toán') {
            $order->update([
                'phuong_thuc_thanh_toan' => 'chuyển khoản',
                'trang_thai_thanh_toan' => 'đã thanh toán',
                'trang_thai_don' => in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan'], true)
                    ? 'đã xác nhận'
                    : $order->trang_thai_don,
            ]);
            $this->paymentService->syncThanhToanRecord($order, 'chuyển khoản', 'đã thanh toán');
            TableStatusService::refreshForTable($order->ban_an_id);
        }

        return redirect()->route('cart.success')->with('order_code', $order->ma_don_hang);
    }

    // ── Khách vãng lai hủy đơn ───────────────────────────────────────────
    public function cancelGuest(Request $request)
    {
        $request->validate(['order_code' => 'required|string']);

        $order = DonHang::where('ma_don_hang', $request->order_code)->first();

        if (!$order) {
            return back()->with('error', 'Không tìm thấy đơn hàng.');
        }

        if (!in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan'], true)) {
            return back()->with('error', 'Chỉ có thể hủy đơn khi ở trạng thái chờ xác nhận.');
        }

        $order->update([
            'trang_thai_don' => 'đã hủy',
            'ghi_chu' => trim($order->ghi_chu . "\n(Khách hàng đã hủy đơn)"),
        ]);

        OrderNotificationService::notifyCustomerCancelled($order->fresh());
        TableStatusService::refreshForTable($order->ban_an_id);

        return redirect()->route('menu.index')->with('success', 'Đã hủy đơn hàng ' . $order->ma_don_hang . ' thành công. Bạn có thể chọn món lại.');
    }

    // ── Internal helper ───────────────────────────────────────────
    private function createOrder(array $attrs, array $cart): DonHang
    {
        $tamTinh = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

        $order = DonHang::create(array_merge([
            'ma_don_hang' => 'ORD-' . strtoupper(Str::random(8)),
            'tam_tinh' => $tamTinh,
            'so_tien_giam' => 0,
            'tong_tien' => $tamTinh,
        ], $attrs));

        foreach ($cart as $item) {
            ChiTietDonHang::create([
                'don_hang_id' => $order->id,
                'ban_an_id' => $order->ban_an_id,
                'san_pham_id' => $item['product_id'],
                'kich_co_id' => $item['size_id'] ?? null,
                'ten_san_pham' => $item['name'],
                'ten_kich_co' => null,
                'don_gia' => $item['price'],
                'so_luong' => $item['qty'],
                'thanh_tien' => $item['price'] * $item['qty'],
                'ghi_chu_mon' => $item['note'] ?? null,
            ]);
        }

        return $order;
    }
}
