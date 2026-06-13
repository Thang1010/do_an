<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherNguoiDung;
use App\Services\VoucherAssignmentService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherAssignmentService $voucherAssignmentService)
    {
    }

    public function claim(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $now = now();

        if ($voucher->trang_thai !== 'đang hoạt động') {
            return back()->with('error', 'Voucher không còn hoạt động.');
        }

        if (($voucher->ngay_bat_dau && $now->lt($voucher->ngay_bat_dau)) ||
            ($voucher->ngay_ket_thuc && $now->gt($voucher->ngay_ket_thuc))) {
            return back()->with('error', 'Voucher không nằm trong thời gian sự kiện.');
        }

        $userId = auth()->id();

        $exists = VoucherNguoiDung::where('nguoi_dung_id', $userId)
            ->where('voucher_id', $voucher->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Bạn đã nhận voucher này rồi.');
        }

        // Kiểm tra giới hạn số lượng
        $hasLimit = (int) ($voucher->so_luong ?? 0) > 0;
        if ($hasLimit) {
            $issued = VoucherNguoiDung::where('voucher_id', $voucher->id)->count();
            if ($issued >= (int) $voucher->so_luong) {
                return back()->with('error', 'Voucher này đã được phát hết.');
            }
        }

        VoucherNguoiDung::create([
            'nguoi_dung_id' => $userId,
            'voucher_id'    => $voucher->id,
            'trang_thai'    => 'chưa dùng',
            'duoc_cap_luc'  => $now,
        ]);

        return back()->with('success', 'Đã nhận thành công voucher: ' . $voucher->ten_voucher);
    }

    public function claimAll(Request $request)
    {
        $user = auth()->user();
        $assigned = $this->voucherAssignmentService->assignLoginEligibleVouchers($user);

        if ($assigned->isEmpty()) {
            return back()->with('info', 'Không còn voucher mới nào để nhận.');
        }

        return back()->with('success', 'Đã nhận thành công ' . $assigned->count() . ' voucher!');
    }
}
