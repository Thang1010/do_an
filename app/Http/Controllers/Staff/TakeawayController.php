<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TakeawayController extends Controller
{
    /**
     * Hàng đợi đơn mang về: đơn đã thanh toán, không gắn bàn, chưa giao.
     */
    public function index()
    {
        $orders = DonHang::query()
            ->takeawayQueue()
            ->with(['chiTietDonHang', 'nguoiDung'])
            ->latest()
            ->get();

        // Đơn mang về đã giao trong hôm nay (tham khảo).
        $deliveredToday = DonHang::query()
            ->whereNull('ban_an_id')
            ->whereNotNull('da_giao_luc')
            ->whereHas('chiTietDonHang', fn($q) => $q->where('loai_don', 'mang về'))
            ->whereDate('da_giao_luc', today())
            ->with(['chiTietDonHang', 'nguoiDung'])
            ->latest('da_giao_luc')
            ->get();

        return view('staff.takeaway.index', compact('orders', 'deliveredToday'));
    }

    /**
     * Nhân viên xác nhận đã giao đơn mang về → rời khỏi hàng đợi.
     */
    public function markDelivered(int $id)
    {
        $order = DonHang::query()
            ->whereNull('ban_an_id')
            ->whereHas('chiTietDonHang', fn($q) => $q->where('loai_don', 'mang về'))
            ->findOrFail($id);

        if ($order->da_giao_luc) {
            return redirect()->route('staff.takeaway.index')
                ->with('warning', "Đơn {$order->ma_don_hang} đã được đánh dấu giao trước đó.");
        }

        $order->update([
            'da_giao_luc' => now(),
            'nhan_vien_id' => $order->nhan_vien_id ?? Auth::id(),
        ]);

        return redirect()->route('staff.takeaway.index')
            ->with('success', "Đã giao đơn mang về {$order->ma_don_hang}.");
    }
}
