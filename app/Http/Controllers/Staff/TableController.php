<?php

namespace App\Http\Controllers\Staff;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\NguyenLieu;
use App\Models\SanPham;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\DanhMuc;
use App\Models\ThanhToan;
use App\Models\Voucher;
use App\Services\PaymentService;
use App\Traits\GeneratesOrderCode;
use App\Traits\NormalizesPayment;
use App\Traits\ResolvesVietQrBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableController extends Controller
{
    use NormalizesPayment, GeneratesOrderCode, ResolvesVietQrBank;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedTableId = $request->filled('table') ? (int) $request->table : null;

        $tables = BanAn::query()
            ->with(['donHang' => function ($q) {
                $q->where('trang_thai_don', '!=', 'đã hủy')
                  ->with(['nguoiDung', 'nhanVien']);
            }])
            ->orderBy('so_ban')
            ->get();

        // Current shift & attendance
        [$currentShift, $currentAttendance] = $this->resolveCurrentShift($user);

        // Ingredients for expense form
        $ingredients = NguyenLieu::orderBy('ten_nguyen_lieu')->get();

        // Selected table detail
        $selectedTable = null;
        $selectedOrder = null;
        $assignOrder = null;
        if ($request->filled('assign_order')) {
            $assignOrder = DonHang::with(['chiTietDonHang.sanPham', 'chiTietDonHang.kichCo'])->find($request->input('assign_order'));
        }
        $selectedItems = collect();
        $store = null;
        $menuCategories = collect();
        $menuProducts = collect();
        $selectedCategoryId = $request->input('category');
        $selectedProductId = $request->input('selected_product');
        $selectedVoucherId = $request->input('voucher_id');
        $availableVouchers = collect();

        if ($request->filled('table')) {
            $selectedTable = BanAn::find($request->table);

            if ($selectedTable) {
                $selectedOrder = $selectedTable->donHang()
                    ->where('trang_thai_don', '!=', 'đã hủy')
                    ->where('trang_thai_thanh_toan', 'chưa thanh toán')
                    ->latest()
                    ->first();

                if ($selectedOrder) {
                    $selectedItems = ChiTietDonHang::where('don_hang_id', $selectedOrder->id)
                        ->with(['sanPham', 'kichCo'])
                        ->get();
                }
            }
        } elseif ($assignOrder) {
            $selectedOrder = $assignOrder;
            $selectedItems = $assignOrder->chiTietDonHang;
        }

                // Store info for payment
                $storeId = $user->cua_hang_id;
                $store = CuaHang::query()
                    ->with('chuCuaHang')
                    ->when($storeId, fn($q) => $q->where('id', $storeId))
                    ->first();

                if (!$store || !$store->so_tai_khoan) {
                    $store = CuaHang::query()
                        ->with('chuCuaHang')
                        ->whereNotNull('so_tai_khoan')
                        ->whereNotNull('ngan_hang')
                        ->first();
                }

                $menuCategories = DanhMuc::query()
                    ->where('trang_thai', 'đang dùng')
                    ->orderBy('ten_danh_muc')
                    ->with(['sanPham' => function ($q) {
                        $q->where('trang_thai_ban', 'đang bán')
                            ->orderBy('ten_san_pham');
                    }])
                    ->get();

                $now = now();
                $availableVouchers = Voucher::query()
                    ->where('trang_thai', 'đang hoạt động')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('ngay_bat_dau')->orWhere('ngay_bat_dau', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>=', $now);
                    })
                    ->orderBy('ma_voucher')
                    ->get();

                if (!$selectedCategoryId && $menuCategories->isNotEmpty()) {
                    $selectedCategoryId = $menuCategories->first()->id;
                }

                $menuProducts = $menuCategories
                    ->firstWhere('id', (int) $selectedCategoryId)?->sanPham
                    ?? collect();

        if ($request->ajax() && $request->boolean('partial')) {
            return response()->json([
                'left' => view('staff.tables.partials.left-panel', compact(
                    'tables', 'currentShift', 'currentAttendance', 'ingredients',
                    'selectedTable', 'selectedOrder', 'selectedItems', 'store', 'menuCategories',
                    'menuProducts', 'selectedCategoryId', 'selectedProductId', 'selectedVoucherId',
                    'availableVouchers', 'assignOrder'
                ))->render(),
                'detail' => view('staff.tables.partials.detail-panel', compact(
                    'selectedTable', 'selectedOrder', 'selectedItems', 'store', 'availableVouchers', 'selectedVoucherId'
                ))->render(),
            ]);
        }

        return view('staff.tables.index', compact(
            'tables', 'currentShift', 'currentAttendance', 'ingredients',
            'selectedTable', 'selectedOrder', 'selectedItems', 'store', 'menuCategories',
            'menuProducts', 'selectedCategoryId', 'selectedProductId', 'selectedVoucherId',
            'availableVouchers', 'assignOrder'
        ));
    }

    public function assignOrder(Request $request, int $tableId)
    {
        $request->validate(['order_id' => 'required|exists:don_hang,id']);
        $table = BanAn::findOrFail($tableId);
        
        if (!in_array($table->trang_thai, ['trống', 'đang chờ duyệt'])) {
             return back()->with('error', 'Bàn này đã có người ngồi, vui lòng chọn bàn trống.');
        }

        $order = DonHang::findOrFail($request->order_id);
        $order->update([
             'ban_an_id' => $table->id,
             'loai_don' => 'mua tại quán',
        ]);

        $order->chiTietDonHang()->update([
             'ban_an_id' => $table->id,
        ]);
        
        $table->update(['trang_thai' => 'đang chờ duyệt']);

        return redirect()->route('staff.tables.index', ['table' => $table->id])
                         ->with('success', 'Đã gán đơn hàng vào bàn thành công.');
    }

    public function show(int $id)
    {
        $table = BanAn::findOrFail($id);

        $dishItems = $table->chiTietDonHang()
            ->whereHas('donHang', function ($q) {
                $q->where('trang_thai_don', '!=', 'đã hủy')
                  ->where('trang_thai_thanh_toan', 'chưa thanh toán');
            })
            ->with(['donHang', 'kichCo'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalPayable = $table->donHang()
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->where('trang_thai_thanh_toan', 'chưa thanh toán')
            ->sum('tong_tien');

        $latestOrder = $table->donHang()
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->where('trang_thai_thanh_toan', 'chưa thanh toán')
            ->with(['nguoiDung', 'nhanVien'])
            ->latest()
            ->first();

        return view('staff.tables.show', compact('table', 'dishItems', 'totalPayable', 'latestOrder'));
    }

    public function generatePaymentQr(Request $request, int $id): JsonResponse
    {
        $table = BanAn::findOrFail($id);

        $order = $table->donHang()
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->where('trang_thai_thanh_toan', 'chưa thanh toán')
            ->latest()
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Bàn này chưa có đơn cần thanh toán.'], 422);
        }

        $storeId = $request->user()?->cua_hang_id;
        $store = CuaHang::query()
            ->with('chuCuaHang')
            ->when($storeId, fn($q) => $q->where('id', $storeId))
            ->first();

        if (!$store || !$store->so_tai_khoan || !$store->ngan_hang) {
            $store = CuaHang::query()
                ->with('chuCuaHang')
                ->whereNotNull('so_tai_khoan')
                ->whereNotNull('ngan_hang')
                ->first();
        }

        if (!$store || !$store->so_tai_khoan || !$store->ngan_hang) {
            return response()->json(['message' => 'Chưa có thông tin tài khoản thanh toán.'], 422);
        }

        $bankCode = $this->resolveVietQrBankCode($store->ngan_hang);
        $accountNo = preg_replace('/\s+/', '', (string) $store->so_tai_khoan);
        $amount = (int) round((float) ($order->tong_tien ?? 0));

        if ($bankCode === '' || $accountNo === '' || $amount <= 0) {
            return response()->json(['message' => 'Thông tin thanh toán không hợp lệ.'], 422);
        }

        $transferContent = 'TT ' . ($order->ma_don_hang ?? ('DON' . $order->id));
        $accountName = $store->chuCuaHang?->ho_ten ?? $store->ten_cua_hang;
        $expiresAt = now()->addSeconds(60);

        $params = http_build_query([
            'amount' => $amount,
            'addInfo' => $transferContent,
            'accountName' => $accountName,
        ], '', '&', PHP_QUERY_RFC3986);

        $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNo}-compact2.png?{$params}";

        return response()->json([
            'message' => 'Đã tạo QR thanh toán. Mã sẽ hết hiệu lực sau 60 giây.',
            'qr_url' => $qrUrl,
            'order_id' => $order->id,
            'order_code' => $order->ma_don_hang,
            'amount' => $amount,
            'bank_name' => $store->ngan_hang,
            'account_no' => $accountNo,
            'account_name' => $accountName,
            'transfer_content' => $transferContent,
            'expires_in' => 60,
        ]);
    }

    public function updatePayment(Request $request, int $id)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'phuong_thuc_thanh_toan' => 'required|string',
            'trang_thai_thanh_toan' => 'required|string',
        ]);

        $table = BanAn::findOrFail($id);
        $order = DonHang::where('id', (int) $request->order_id)
            ->where('ban_an_id', $table->id)
            ->firstOrFail();

        $paymentMethod = $this->normalizePaymentMethod($request->phuong_thuc_thanh_toan);
        $paymentStatus = $this->normalizePaymentStatus($request->trang_thai_thanh_toan);

        if (!$paymentStatus || !$paymentMethod) {
            return back()->with('error', 'Thông tin thanh toán không hợp lệ.');
        }

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $table) {
            $order->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
                'nhan_vien_id' => Auth::id(),
            ]);

            $this->paymentService->syncThanhToanSimple($order, $paymentMethod, $paymentStatus);

            if ($paymentStatus === 'đã thanh toán') {
                $this->paymentService->freeTableIfAllPaid($table->id);
            }
        });

        return redirect()
            ->route('staff.tables.index', ['table' => $table->id])
            ->with('success', "Đã cập nhật thanh toán cho bàn {$table->so_ban}.");
    }

    public function addItem(Request $request, int $id)
    {
        $request->validate([
            'san_pham_id' => 'required|exists:san_pham,id',
            'order_id' => 'nullable|exists:don_hang,id',
            'category_id' => 'nullable|integer',
        ]);

        $table = BanAn::findOrFail($id);
        $product = SanPham::findOrFail($request->san_pham_id);
        $categoryId = $request->input('category_id') ?: $product->danh_muc_id;

        $order = null;
        if ($request->filled('order_id')) {
            $order = DonHang::where('id', (int) $request->order_id)
                ->where('ban_an_id', $table->id)
                ->firstOrFail();
        }

        if (!$order) {
            $order = DonHang::create([
                'ma_don_hang' => $this->generateOrderCode(),
                'nhan_vien_id' => Auth::id(),
                'ban_an_id' => $table->id,
                'loai_don' => 'mua tại quán',
                'trang_thai_don' => 'chờ xác nhận',
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => 'tiền mặt',
                'tam_tinh' => 0,
                'so_tien_giam' => 0,
                'tong_tien' => 0,
            ]);

        }

        // Check if item already exists in order
        $existing = ChiTietDonHang::where('don_hang_id', $order->id)
            ->where('san_pham_id', $product->id)
            ->first();

        if ($existing) {
            $existing->update([
                'so_luong' => $existing->so_luong + 1,
                'thanh_tien' => ($existing->don_gia ?? $product->gia_goc) * ($existing->so_luong + 1),
            ]);
        } else {
            $price = $product->gia_khuyen_mai ?? $product->gia_goc;
            ChiTietDonHang::create([
                'don_hang_id' => $order->id,
                'ban_an_id' => $table->id,
                'san_pham_id' => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'ten_kich_co' => 'M',
                'don_gia' => $price,
                'so_luong' => 1,
                'thanh_tien' => $price,
                'created_at' => now(),
            ]);
        }

        // Recalculate order total
        $this->recalculateOrder($order);

        return redirect()
            ->route('staff.tables.index', array_filter([
                'table' => $table->id,
                'category' => $categoryId,
                'selected_product' => $product->id,
            ]));
    }

    public function updateOrderStatus(Request $request, int $tableId)
    {
        $request->validate([
            'order_id' => 'required|exists:don_hang,id',
            'action' => 'required|in:draft,confirm,payment,reject',
            'voucher_id' => 'nullable|exists:voucher,id',
            'category' => 'nullable|integer',
        ]);

        $table = BanAn::findOrFail($tableId);
        $order = DonHang::where('id', (int) $request->order_id)
            ->where('ban_an_id', $table->id)
            ->firstOrFail();

        $voucherId = $request->input('voucher_id');
        $subtotal = $this->calculateSubtotal($order);
        $discount = 0;

        if ($voucherId) {
            $voucher = Voucher::query()->whereKey($voucherId)->first();
            if (!$voucher || $voucher->trang_thai !== 'đang hoạt động') {
                return back()->with('error', 'Voucher không hợp lệ.');
            }

            $now = now();
            if ($voucher->ngay_bat_dau && $voucher->ngay_bat_dau->gt($now)) {
                return back()->with('error', 'Voucher chưa đến thời gian sử dụng.');
            }

            if ($voucher->ngay_ket_thuc && $voucher->ngay_ket_thuc->lt($now)) {
                return back()->with('error', 'Voucher đã hết hạn.');
            }

            $minTotal = (float) ($voucher->don_toi_thieu ?? 0);
            if ($minTotal > 0 && $subtotal < $minTotal) {
                return back()->with('error', 'Đơn hàng chưa đủ điều kiện áp dụng voucher.');
            }

            if ($voucher->loai_giam === 'phần trăm') {
                $discount = $subtotal * ((float) $voucher->gia_tri_giam / 100);
                $maxDiscount = (float) ($voucher->giam_toi_da ?? 0);
                if ($maxDiscount > 0) {
                    $discount = min($discount, $maxDiscount);
                }
            } else {
                $discount = (float) $voucher->gia_tri_giam;
            }

            $discount = min($discount, $subtotal);
        }

        $order->update([
            'tam_tinh' => $subtotal,
            'so_tien_giam' => $discount,
            'tong_tien' => max(0, $subtotal - $discount),
        ]);

        if ($request->action === 'reject') {
            $order->update([
                'trang_thai_don' => 'đã hủy',
                'nhan_vien_id' => Auth::id(),
                'ghi_chu' => trim($order->ghi_chu . "\n(Cửa hàng từ chối đơn)"),
            ]);
            $table->update(['trang_thai' => 'trống']);
            return redirect()
                ->route('staff.tables.index')
                ->with('success', 'Đã từ chối đơn hàng.');
        }

        if ($request->action === 'confirm' || $request->action === 'payment') {
            if ($order->chiTietDonHang()->count() === 0) {
                return redirect()
                    ->route('staff.tables.index', ['table' => $table->id])
                    ->with('error', 'Đơn hàng chưa có món nào.');
            }
            $order->update([
                'trang_thai_don' => 'đã xác nhận',
                'nhan_vien_id' => Auth::id(),
            ]);
            $table->update(['trang_thai' => 'đang phục vụ']);

            $paymentMethod = $order->phuong_thuc_thanh_toan ?? 'tiền mặt';
            $this->paymentService->syncThanhToanSimple($order, $paymentMethod, 'chưa thanh toán');

            if ($request->action === 'payment') {
                return redirect()
                    ->route('staff.tables.index', array_filter([
                        'table' => $table->id,
                        'category' => $request->input('category'),
                        'voucher_id' => $voucherId,
                        'payment' => 1,
                    ]));
            }

            return redirect()
                ->route('staff.tables.index', array_filter([
                    'table' => $table->id,
                    'category' => $request->input('category'),
                    'voucher_id' => $voucherId,
                ]))->with('success', 'Đã xác nhận đơn hàng.');
        } else {
            $table->update(['trang_thai' => 'đang phục vụ']);
            $paymentMethod = $order->phuong_thuc_thanh_toan ?? 'tiền mặt';
            $this->paymentService->syncThanhToanSimple($order, $paymentMethod, 'chưa thanh toán');

            $message = 'Đã cập nhật tạm tính.';
        }

        $redirect = redirect()->route('staff.tables.index', array_filter([
            'table' => $table->id,
            'category' => $request->input('category'),
            'voucher_id' => $voucherId,
        ]));

        if ($request->boolean('auto_voucher')) {
            return $redirect;
        }

        return $redirect->with('success', $message);
    }

    public function updateItemQuantity(Request $request, int $tableId, int $itemId)
    {
        $table = BanAn::findOrFail($tableId);
        $item = ChiTietDonHang::findOrFail($itemId);

        $action = $request->input('action', 'increase');

        if ($action === 'decrease') {
            if ($item->so_luong <= 1) {
                $item->delete();
            } else {
                $item->update([
                    'so_luong' => $item->so_luong - 1,
                    'thanh_tien' => $item->don_gia * ($item->so_luong - 1),
                ]);
            }
        } else {
            $item->update([
                'so_luong' => $item->so_luong + 1,
                'thanh_tien' => $item->don_gia * ($item->so_luong + 1),
            ]);
        }

        // Recalculate order total
        if ($item->don_hang_id) {
            $order = DonHang::find($item->don_hang_id);
            if ($order) $this->recalculateOrder($order);
        }

        return redirect()
            ->route('staff.tables.index', ['table' => $table->id]);
    }

    // ─── Helpers ───

    private function resetDailyTableOrders($user): void
    {
        $today = now()->toDateString();

        $staleOrders = DonHang::query()
            ->where('loai_don', 'mua tại quán')
            ->whereNotNull('ban_an_id')
            ->where('nhan_vien_id', $user->id)
            ->where('trang_thai_thanh_toan', 'chưa thanh toán')
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->whereDate('created_at', '<', $today)
            ->with('banAn')
            ->get();

        foreach ($staleOrders as $order) {
            $hasDraft = ThanhToan::where('don_hang_id', $order->id)->exists();

            if ($hasDraft) {
                $order->update(['trang_thai_thanh_toan' => 'chưa thanh toán']);
                continue;
            }

            $table = $order->banAn;
            $order->delete();

            if ($table && $table->trang_thai === 'đang phục vụ') {
                $hasUnpaid = DonHang::where('ban_an_id', $table->id)
                    ->where('trang_thai_don', '!=', 'đã hủy')
                    ->where('trang_thai_thanh_toan', 'chưa thanh toán')
                    ->exists();

                if (!$hasUnpaid) {
                    $table->update(['trang_thai' => 'trống']);
                }
            }
        }
    }

    private function recalculateOrder(DonHang $order): void
    {
        $subtotal = $this->calculateSubtotal($order);

        $order->update([
            'tam_tinh' => $subtotal,
            'tong_tien' => max(0, $subtotal - ($order->so_tien_giam ?? 0)),
        ]);
    }

    private function calculateSubtotal(DonHang $order): float
    {
        return (float) (ChiTietDonHang::where('don_hang_id', $order->id)
            ->selectRaw('SUM(don_gia * so_luong) as total')
            ->value('total') ?? 0);
    }

    private function resolveCurrentShift($user): array
    {
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $shift = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', $today)
            ->where('gio_bat_dau', '<=', $currentTime)
            ->where('gio_ket_thuc', '>=', $currentTime)
            ->first();

        if (!$shift) {
            // Try any shift today
            $shift = CaLamViec::where('nguoi_dung_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->orderBy('gio_bat_dau')
                ->first();
        }

        $attendance = null;
        if ($shift) {
            $attendance = ChamCong::where('nguoi_dung_id', $user->id)
                ->where('ca_lam_viec_id', $shift->id)
                ->latest()
                ->first();
        }

        return [$shift, $attendance];
    }

    // resolveVietQrBankCode() => ResolvesVietQrBank trait
    // normalizePaymentMethod() => NormalizesPayment trait
    // normalizePaymentStatus() => NormalizesPayment trait
    // generateOrderCode() => GeneratesOrderCode trait
}
