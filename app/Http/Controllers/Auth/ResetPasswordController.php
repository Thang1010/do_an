<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->session()->get('password_reset_user_id');
        if (! $userId) {
            return redirect()
                ->route('auth.forgot-password')
                ->withErrors(['email' => 'Vui lòng nhập email để nhận mã xác thực.']);
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get('password_reset_email'),
            'isVerified' => $request->session()->get('password_reset_verified', false),
        ]);
    }

    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực.',
            'code.size' => 'Mã xác thực gồm 6 chữ số.',
        ]);

        $userId = $request->session()->get('password_reset_user_id');
        $codeId = $request->session()->get('password_reset_code_id');

        if (! $userId || ! $codeId) {
            return redirect()
                ->route('auth.forgot-password')
                ->withErrors(['email' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thử lại.']);
        }

        $record = EmailVerificationCode::query()
            ->where('id', $codeId)
            ->where('nguoi_dung_id', $userId)
            ->first();

        if (! $record) {
            return back()->withErrors(['code' => 'Không tìm thấy mã xác thực. Vui lòng gửi lại mã.']);
        }

        if ($record->xac_minh_luc) {
            return back()->withErrors(['code' => 'Mã xác thực đã được sử dụng.']);
        }

        if ($record->het_han_luc && $record->het_han_luc->isPast()) {
            return back()->withErrors(['code' => 'Mã xác thực đã hết hạn. Vui lòng gửi lại mã mới.']);
        }

        if (! Hash::check($data['code'], $record->ma_xac_minh_ma_hoa)) {
            return back()->withErrors(['code' => 'Mã xác thực không đúng.']);
        }

        $record->update([
            'xac_minh_luc' => now(),
        ]);

        $request->session()->put('password_reset_verified', true);

        return redirect()
            ->route('auth.reset-password')
            ->with('status', 'Mã xác thực hợp lệ. Vui lòng đặt mật khẩu mới.');
    }

    public function update(Request $request)
    {
        $userId = $request->session()->get('password_reset_user_id');
        $codeId = $request->session()->get('password_reset_code_id');

        if (! $userId || ! $request->session()->get('password_reset_verified')) {
            return redirect()
                ->route('auth.reset-password')
                ->withErrors(['password' => 'Vui lòng xác minh mã trước khi đặt mật khẩu mới.']);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $nguoiDung = NguoiDung::query()->find($userId);
        if (! $nguoiDung) {
            return redirect()
                ->route('auth.forgot-password')
                ->withErrors(['email' => 'Không tìm thấy tài khoản cần đặt lại mật khẩu.']);
        }

        $nguoiDung->update([
            'mat_khau' => Hash::make($request->input('password')),
            'email_da_xac_thuc_luc' => $nguoiDung->email_da_xac_thuc_luc ?: now(),
        ]);

        if ($codeId) {
            EmailVerificationCode::query()->where('id', $codeId)->delete();
        }

        $request->session()->forget([
            'password_reset_user_id',
            'password_reset_email',
            'password_reset_code_id',
            'password_reset_verified',
        ]);

        return redirect()
            ->route('auth.login')
            ->with('success', 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập.');
    }
}
