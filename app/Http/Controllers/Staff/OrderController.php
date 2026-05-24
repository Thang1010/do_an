<?php

namespace App\Http\Controllers\Staff;

use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\ThanhToan;
use App\Services\PaymentService;
use App\Traits\NormalizesPayment;
use App\Traits\ResolvesVietQrBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use NormalizesPayment, ResolvesVietQrBank;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = DonHang::query()
            ->with(['banAn', 'nguoiDung', 'nhanVien'])
            ->where(function ($q) use ($user) {
                $q->where('nhan_vien_id', $user->id)
                  ->orWhereHas('banAn'); // show all table orders for staff
            })
            ->latest();

        if ($request->filled('order_code')) {
            $code = trim((string) $request->order_code);
            $query->where('ma_don_hang', 'like', "%{$code}%");
        }

        if ($request->filled('customer_name')) {
            $name = trim((string) $request->customer_name);
            $query->where(function ($q) use ($name) {
                $q->whereHas('nguoiDung', fn($q2) => $q2->where('ho_ten', 'like', "%{$name}%"))
                  ->orWhere('ten_khach_hang', 'like', "%{$name}%");
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('staff.orders.index', compact('orders'));
    }

    public function show(int $id)
    {
        $order = DonHang::with(['banAn', 'nguoiDung', 'nhanVien', 'chiTietDonHang.sanPham', 'chiTietDonHang.kichCo'])
            ->findOrFail($id);
        $user = Auth::user();

        $store = $this->paymentService->resolveStoreForPayment($user?->cua_hang_id);

        return view('staff.orders.show', compact('order', 'store'));
    }

    public function generatePaymentQr(Request $request, int $id): JsonResponse
    {
        $order = DonHang::with('banAn')->findOrFail($id);

        if ($order->trang_thai_don === 'đã hủy') {
            return response()->json(['message' => 'Đơn hàng đã hủy.'], 422);
        }

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return response()->json(['message' => 'Đơn hàng đã được thanh toán.'], 422);
        }

        $storeId = $request->user()?->cua_hang_id;
        $store = $this->paymentService->resolveStoreForPayment($storeId);

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
            'phuong_thuc_thanh_toan' => 'required|string',
            'trang_thai_thanh_toan' => 'required|string',
        ]);

        $order = DonHang::with('banAn')->findOrFail($id);

        $paymentMethod = $this->normalizePaymentMethod($request->phuong_thuc_thanh_toan);
        $paymentStatus = $this->normalizePaymentStatus($request->trang_thai_thanh_toan);

        if (!$paymentStatus || !$paymentMethod) {
            return back()->with('error', 'Thông tin thanh toán không hợp lệ.');
        }

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus) {
            $order->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
                'nhan_vien_id' => Auth::id(),
            ]);

            $this->paymentService->syncThanhToanSimple($order, $paymentMethod, $paymentStatus);

            if ($paymentStatus === 'đã thanh toán') {
                $this->paymentService->freeTableIfAllPaid($order->ban_an_id);
            }
        });

        return redirect()
            ->route('staff.orders.show', ['id' => $order->id])
            ->with('success', "Đã cập nhật thanh toán cho đơn #{$order->id}.");
    }

    // resolveVietQrBankCode() => ResolvesVietQrBank trait
    // normalizePaymentMethod() => NormalizesPayment trait
    // normalizePaymentStatus() => NormalizesPayment trait
}
