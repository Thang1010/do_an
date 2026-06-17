<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordSetupController extends Controller
{
    public function show()
    {
        $user = Auth::guard('nguoi_dung')->user();
        if (! $user) {
            return redirect()->route('auth.login');
        }

        if ($user->mat_khau) {
            return $this->redirectByRole($user);
        }

        return view('auth.set-password');
    }

    public function update(Request $request)
    {
        $user = Auth::guard('nguoi_dung')->user();
        if (! $user) {
            return redirect()->route('auth.login');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user->update([
            'mat_khau' => Hash::make($request->input('password')),
            'email_da_xac_thuc_luc' => $user->email_da_xac_thuc_luc ?: now(),
        ]);

        $request->session()->forget('force_password_setup');

        // Đặt mật khẩu xong mới hiển thị popup nhận voucher (đúng thứ tự cho tài khoản mới).
        if ($request->session()->pull('pending_voucher_popup') && $user->vai_tro === 'khách hàng') {
            $request->session()->flash('show_voucher_popup', true);
        }

        return $this->redirectByRole($user)
            ->with('success', 'Đã cập nhật mật khẩu.');
    }

    private function redirectByRole(NguoiDung $nguoiDung)
    {
        return match ($nguoiDung->vai_tro) {
            'quản lý', 'chủ cửa hàng' => redirect()->route('manager.dashboard'),
            'nhân viên' => redirect()->route('staff.dashboard'),
            default => redirect()->route('home'),
        };
    }
}
