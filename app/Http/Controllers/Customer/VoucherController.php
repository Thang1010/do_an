<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherNguoiDung;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function claim(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        
        if ($voucher->trang_thai !== 'hoạt động' || now()->lt($voucher->ngay_bat_dau) || now()->gt($voucher->ngay_ket_thuc)) {
            return back()->with('error', 'Voucher không khả dụng hoặc đã hết hạn.');
        }

        $userId = auth()->id();

        $exists = VoucherNguoiDung::where('nguoi_dung_id', $userId)
            ->where('voucher_id', $voucher->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Bạn đã nhận voucher này rồi.');
        }

        VoucherNguoiDung::create([
            'nguoi_dung_id' => $userId,
            'voucher_id' => $voucher->id,
            'trang_thai' => 'chưa dùng'
        ]);

        return back()->with('success', 'Bạn đã nhận thành công voucher: ' . $voucher->ten_voucher);
    }
}
