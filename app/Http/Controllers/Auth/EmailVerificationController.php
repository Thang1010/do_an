<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\PendingCustomerRegistration;
use App\Notifications\CustomerEmailVerificationNotification;
use App\Models\CuaHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EmailVerificationController extends Controller
{
    public function show(Request $request)
    {
        $prefillEmail = $request->session()->get('pending_verification_email');

        return view('auth.verify-email', [
            'prefillEmail' => $prefillEmail,
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'code' => 'required|string|size:6',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Vui lòng nhập mã xác minh.',
            'code.size' => 'Mã xác minh gồm 6 chữ số.',
        ]);

        $email = strtolower(trim($data['email']));
        $pending = PendingCustomerRegistration::query()
            ->where('email', $email)
            ->first();

        if (! $pending) {
            $existing = NguoiDung::query()
                ->where('email', $email)
                ->where('vai_tro', 'khách hàng')
                ->first();

            if ($existing) {
                return redirect()->route('auth.login')
                    ->with('success', 'Tài khoản đã được kích hoạt. Vui lòng đăng nhập.');
            }

            return back()->withErrors(['email' => 'Không tìm thấy yêu cầu đăng ký với email này.'])->withInput();
        }

        if ($pending->het_han_luc && $pending->het_han_luc->isPast()) {
            return back()->withErrors(['code' => 'Mã xác minh đã hết hạn. Vui lòng gửi lại mã mới.']);
        }

        if (! Hash::check($data['code'], $pending->ma_xac_minh_ma_hoa)) {
            return back()->withErrors(['code' => 'Mã xác minh không đúng.'])->withInput();
        }

        DB::transaction(function () use ($pending): void {
            $store = CuaHang::query()->orderBy('id')->first();
            $nguoiDung = NguoiDung::create([
                'cua_hang_id' => $store?->id,
                'ho_ten' => $pending->ho_ten,
                'email' => $pending->email,
                'mat_khau' => $pending->mat_khau_ma_hoa,
                'vai_tro' => 'khách hàng',
                'trang_thai' => 'hoạt động',
                'email_da_xac_thuc_luc' => now(),
            ]);

            $nguoiDung->hoSoKhachHang()->firstOrCreate([
                'nguoi_dung_id' => $nguoiDung->id,
            ]);

            $pending->delete();
        });

        $request->session()->forget('pending_verification_email');

        return redirect()->route('auth.login')
            ->with('success', 'Xác minh thành công. Bạn có thể đăng nhập.');
    }

    public function resend(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $email = strtolower(trim($data['email']));
        $pending = PendingCustomerRegistration::query()
            ->where('email', $email)
            ->first();

        if (! $pending) {
            $existing = NguoiDung::query()
                ->where('email', $email)
                ->where('vai_tro', 'khách hàng')
                ->first();

            if ($existing) {
                return redirect()->route('auth.login')
                    ->with('success', 'Tài khoản đã được kích hoạt. Vui lòng đăng nhập.');
            }

            return back()->withErrors(['email' => 'Không tìm thấy yêu cầu đăng ký với email này.'])->withInput();
        }

        $this->sendVerificationCode($pending);

        $request->session()->put('pending_verification_email', $email);

        return back()->with('success', 'Đã gửi lại mã xác minh.');
    }

    private function sendVerificationCode(PendingCustomerRegistration $pending): void
    {
        $code = (string) random_int(100000, 999999);

        $pending->update([
            'ma_xac_minh_ma_hoa' => Hash::make($code),
            'het_han_luc' => now()->addMinutes(10),
        ]);

        try {
            Notification::route('mail', $pending->email)
                ->notify(new CustomerEmailVerificationNotification($code));
        } catch (\Throwable $e) {
            Log::warning('Không thể gửi mã xác minh email.', [
                'email' => $pending->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
