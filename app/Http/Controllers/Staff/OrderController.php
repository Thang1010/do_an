<?php

namespace App\Http\Controllers\Staff;

use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\NguoiDung;
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

        // Chỉ hiển thị đơn do chính tài khoản nhân viên này tạo
        // (không phải toàn bộ đơn của cửa hàng).
        $query = DonHang::query()
            ->with(['banAn', 'nguoiDung', 'nhanVien', 'chiTietDonHang'])
            ->where('nhan_vien_id', $user->id)
            ->latest();

        if ($request->filled('order_code')) {
            $code = trim((string) $request->order_code);
            $query->where('ma_don_hang', 'like', "%{$code}%");
        }

        if ($request->filled('customer_name')) {
            $name = trim((string) $request->customer_name);
            $query->where(function ($q) use ($name) {
                $q->whereHas('nguoiDung', fn($q2) => $q2->where('email', 'like', "%{$name}%"))
                  ->orWhere('email_khach_hang', 'like', "%{$name}%");
            });
        }

        if (!$request->has('date_start') && !$request->has('date_end')) {
            $now = now();
            $defaultStart = $now->copy()->startOfMonth()->toDateString();
            $defaultEnd = $now->copy()->endOfMonth()->toDateString();
            $request->merge([
                'date_start' => $defaultStart,
                'date_end' => $defaultEnd,
            ]);
        }

        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('staff.orders.index', compact('orders', 'dateStart', 'dateEnd'));
    }

    public function show(int $id)
    {
        $order = DonHang::with(['banAn', 'nguoiDung', 'nhanVien', 'chiTietDonHang.sanPham', 'chiTietDonHang.kichCo'])
            ->findOrFail($id);
        $user = Auth::user();

        $store = $this->paymentService->resolveStoreForPayment($user?->cua_hang_id);

        return view('staff.orders.show', compact('order', 'store'));
    }


    public function updatePayment(Request $request, int $id)
    {
        $request->validate([
            'phuong_thuc_thanh_toan' => 'required|string',
            'trang_thai_thanh_toan' => 'required|string',
            'email_khach_hang' => 'nullable|email|max:255',
        ]);

        $order = DonHang::with('banAn')->findOrFail($id);

        $paymentMethod = $this->normalizePaymentMethod($request->phuong_thuc_thanh_toan);
        $paymentStatus = $this->normalizePaymentStatus($request->trang_thai_thanh_toan);

        if (!$paymentStatus || !$paymentMethod) {
            return back()->with('error', 'Thông tin thanh toán không hợp lệ.');
        }

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $request) {
            $emailKhachHang = $request->email_khach_hang ?? $order->email_khach_hang;

            $updateData = [
                'nhan_vien_id' => Auth::id(),
                'email_khach_hang' => $emailKhachHang,
            ];

            // Đơn order tại quầy chưa gắn tài khoản: nếu email khớp một tài khoản
            // khách hàng đã đăng ký thì tự liên kết để khách vẫn có quyền đánh giá
            // khi đăng nhập lại (dùng lại logic đánh giá theo nguoi_dung_id).
            if (!$order->nguoi_dung_id) {
                $khachHang = NguoiDung::khachHangByEmail($emailKhachHang);
                if ($khachHang) {
                    $updateData['nguoi_dung_id'] = $khachHang->id;
                }
            }

            $order->update($updateData);

            $order->updatePaymentStatus($paymentStatus, $paymentMethod);

            $this->paymentService->syncThanhToanSimple($order, $paymentMethod, $paymentStatus);

            if ($paymentStatus === 'đã thanh toán') {
                $order = $order->fresh();
                $this->paymentService->applyTableStatusAfterPayment($order);
                
                if ($order->email_khach_hang) {
                    \Illuminate\Support\Facades\Mail::to($order->email_khach_hang)->queue(new \App\Mail\CustomerOrderPaidMail($order));
                } elseif ($order->nguoiDung && $order->nguoiDung->email) {
                    \Illuminate\Support\Facades\Mail::to($order->nguoiDung->email)->queue(new \App\Mail\CustomerOrderPaidMail($order));
                }
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
