<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\DonHang;
use App\Models\KichCo;
use App\Models\SanPham;
use App\Models\VoucherNguoiDung;
use App\Services\OrderNotificationService;
use App\Services\TableStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = DonHang::query()
            ->with(['banAn'])
            ->withCount('chiTietDonHang')
            ->where('nguoi_dung_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('customer.orders.index', compact('orders'));
    }

    public function show(Request $request, int $id)
    {
        $order = DonHang::query()
            ->with(['banAn', 'chiTietDonHang.sanPham', 'chiTietDonHang.kichCo'])
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        return view('customer.orders.show', compact('order'));
    }

    public function update(Request $request, int $id)
    {
        if (! $request->user()?->isKhachHang()) {
            abort(403);
        }

        $order = DonHang::query()
            ->with('chiTietDonHang')
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        if (! $this->isPending($order)) {
            return $this->reject($request, 'Chỉ có thể sửa đơn khi đang chờ xác nhận.');
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.san_pham_id' => 'required|integer|exists:san_pham,id',
            'items.*.kich_co_id' => 'nullable|integer|exists:kich_co,id',
            'items.*.so_luong' => 'required|integer|min:1',
            'items.*.ghi_chu_mon' => 'nullable|string|max:255',
            'items.*.note' => 'nullable|string|max:255',
            'ghi_chu' => 'nullable|string|max:500',
            'voucher_nguoi_dung_id' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($order, $validated): void {
            if (array_key_exists('ghi_chu', $validated)) {
                $order->ghi_chu = trim((string) $validated['ghi_chu']);
            }

            $order->chiTietDonHang()->delete();

            $tamTinh = 0.0;
            foreach ($validated['items'] as $item) {
                $product = SanPham::query()->findOrFail($item['san_pham_id']);
                $unitPrice = (float) ($product->gia_khuyen_mai ?? $product->gia_goc ?? 0);
                $qty = (int) $item['so_luong'];

                $sizeId = $item['kich_co_id'] ?? null;
                $sizeName = null;
                if ($sizeId) {
                    $sizeName = KichCo::query()->whereKey($sizeId)->value('ten_kich_co');
                }

                $note = trim((string) ($item['ghi_chu_mon'] ?? $item['note'] ?? ''));
                $note = $note !== '' ? $note : null;

                $subtotal = $unitPrice * $qty;
                $tamTinh += $subtotal;

                ChiTietDonHang::create([
                    'don_hang_id' => $order->id,
                    'ban_an_id' => $order->ban_an_id,
                    'san_pham_id' => $product->id,
                    'kich_co_id' => $sizeId,
                    'ten_san_pham' => $product->ten_san_pham,
                    'ten_kich_co' => $sizeName,
                    'don_gia' => $unitPrice,
                    'so_luong' => $qty,
                    'thanh_tien' => $subtotal,
                    'ghi_chu_mon' => $note,
                ]);
            }

            $discount = 0.0;
            $voucherNguoiDungId = $validated['voucher_nguoi_dung_id'] ?? null;
            
            // Xử lý hoàn voucher cũ nếu có đổi voucher
            if ($order->voucher_nguoi_dung_id && $order->voucher_nguoi_dung_id != $voucherNguoiDungId) {
                // Hoàn lại voucher cũ
                $oldVoucher = VoucherNguoiDung::find($order->voucher_nguoi_dung_id);
                if ($oldVoucher && $oldVoucher->trang_thai === 'đã dùng') {
                    $oldVoucher->update(['trang_thai' => 'chưa dùng', 'ngay_su_dung' => null]);
                }
            }

            if ($voucherNguoiDungId) {
                $v = VoucherNguoiDung::with('voucher')->find($voucherNguoiDungId);
                if ($v && $v->voucher && ($v->trang_thai === 'chưa dùng' || $v->id == $order->voucher_nguoi_dung_id)) {
                    if ($tamTinh >= $v->voucher->don_toi_thieu) {
                        $discount = $v->voucher->loai_giam === 'phan_tram' 
                            ? $tamTinh * ($v->voucher->gia_tri_giam / 100) 
                            : (float)$v->voucher->gia_tri_giam;
                        if ($v->voucher->giam_toi_da > 0) {
                            $discount = min($discount, $v->voucher->giam_toi_da);
                        }
                        
                        // Đánh dấu đã dùng nếu chưa dùng
                        if ($v->trang_thai === 'chưa dùng') {
                            $v->update(['trang_thai' => 'đã dùng', 'ngay_su_dung' => now()]);
                        }
                    } else {
                        $voucherNguoiDungId = null; // Bỏ voucher nếu không đủ điều kiện
                    }
                } else {
                    $voucherNguoiDungId = null;
                }
            } else {
                $voucherNguoiDungId = null;
            }

            if ($discount > $tamTinh) {
                $discount = $tamTinh;
            }

            $order->fill([
                'tam_tinh' => $tamTinh,
                'so_tien_giam' => $discount,
                'tong_tien' => max($tamTinh - $discount, 0),
                'voucher_nguoi_dung_id' => $voucherNguoiDungId,
            ])->save();
        });

        OrderNotificationService::notifyCustomerUpdated($order->fresh());

        return $this->success($request, 'Đã cập nhật đơn hàng thành công.');
    }

    public function cancel(Request $request, int $id)
    {
        $order = DonHang::query()
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        if (! $this->isPending($order)) {
            return $this->reject($request, 'Chỉ có thể hủy đơn khi đang chờ xác nhận.');
        }

        $order->update(['trang_thai_don' => OrderStatus::DA_HUY->value]);

        if ($order->ban_an_id) {
            TableStatusService::refreshForTable($order->ban_an_id);
        }

        return back()->with('success', 'Đã hủy đơn hàng.');
    }

    public function editInCart(Request $request, int $id)
    {
        $order = DonHang::query()
            ->with(['chiTietDonHang.sanPham'])
            ->where('nguoi_dung_id', $request->user()->id)
            ->findOrFail($id);

        if (! in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan'])) {
            return back()->with('error', 'Chỉ có thể sửa đơn khi đang chờ xác nhận.');
        }

        // Hủy đơn hàng hiện tại
        $order->update(['trang_thai_don' => OrderStatus::DA_HUY->value]);
        if ($order->ban_an_id) {
            TableStatusService::refreshForTable($order->ban_an_id);
        }

        // Tạo giỏ hàng mới
        $cart = [];
        foreach ($order->chiTietDonHang as $item) {
            if (!$item->san_pham_id) continue;
            $product = $item->sanPham;
            if (!$product) continue;

            $key = $item->san_pham_id . '_' . ($item->kich_co_id ?? 0) . '_' . \Illuminate\Support\Str::random(6);
            $cart[$key] = [
                'product_id' => $item->san_pham_id,
                'size_id' => $item->kich_co_id,
                'qty' => $item->so_luong,
                'note' => $item->ghi_chu_mon,
                'price' => $item->don_gia,
                'name' => $item->ten_san_pham,
                'image' => $product->image_url ?? '',
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Đã đưa đơn hàng vào giỏ để sửa.');
    }

    private function isPending(DonHang $order): bool
    {
        return OrderStatus::normalize($order->trang_thai_don) === OrderStatus::CHO_XAC_NHAN;
    }

    private function reject(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message);
    }

    private function success(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
