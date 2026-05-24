<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use App\Models\NguoiDung;
use App\Notifications\CustomerPasswordResetCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ForgotPasswordController extends Controller
{
    public function show(Request $request)
    {
        $prefillEmail = $request->session()->get('password_reset_email');

        return view('auth.forgot-password', [
            'prefillEmail' => $prefillEmail,
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $email = strtolower(trim($data['email']));
        $nguoiDung = NguoiDung::query()
            ->where('email', $email)
            ->first();

        if (! $nguoiDung) {
            return back()
                ->withErrors(['email' => 'Không tìm thấy tài khoản với email này.'])
                ->withInput();
        }

        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::query()
            ->where('nguoi_dung_id', $nguoiDung->id)
            ->delete();

        $record = EmailVerificationCode::create([
            'nguoi_dung_id' => $nguoiDung->id,
            'ma_xac_minh_ma_hoa' => Hash::make($code),
            'het_han_luc' => now()->addMinutes(10),
        ]);

        try {
            Notification::route('mail', $email)
                ->notify(new CustomerPasswordResetCodeNotification($code));
        } catch (\Throwable $e) {
            Log::warning('Không thể gửi mã đặt lại mật khẩu.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        $request->session()->forget('password_reset_verified');
        $request->session()->put([
            'password_reset_user_id' => $nguoiDung->id,
            'password_reset_email' => $email,
            'password_reset_code_id' => $record->id,
        ]);

        return redirect()
            ->route('auth.reset-password')
            ->with('status', 'Đã gửi mã xác thực đến email của bạn.');
    }
}
