<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{

    /**
     * Hiển thị form đăng nhập.
     */
    public function showLoginForm()
    {
        // Nếu đã đăng nhập thì redirect theo vai trò
        if (Auth::guard('nguoi_dung')->check()) {
            return $this->redirectByRole(Auth::guard('nguoi_dung')->user());
        }

        return view('auth.login');
    }

    /**
     * Xử lý đăng nhập.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string|max:150',
            'password' => 'required|string',
        ], [
            'login.required'    => 'Vui lòng nhập email hoặc số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $loginValue = trim($request->login);

        // Tìm người dùng theo email hoặc số điện thoại
        $nguoiDung = NguoiDung::where('email', $loginValue)
                               ->first();

        if (! $nguoiDung) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Tài khoản không tồn tại trong hệ thống.']);
        }

        // Kiểm tra trạng thái tài khoản
        if ($nguoiDung->trang_thai === 'ngưng hoạt động') {
            if ($nguoiDung->vai_tro === 'khách hàng') {
                $request->session()->put('pending_verification_email', $nguoiDung->email);

                return back()
                    ->withInput($request->only('login', 'remember'))
                    ->withErrors(['login' => 'Tài khoản chưa xác minh email. Vui lòng nhập mã xác minh.']);
            }

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Tài khoản chưa được kích hoạt.']);
        }

        if (! $nguoiDung->mat_khau) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['password' => 'Tài khoản này đăng nhập bằng Google. Vui lòng đăng nhập bằng Google để đặt mật khẩu.']);
        }

        // Kiểm tra mật khẩu
        if (! Hash::check($request->password, $nguoiDung->mat_khau)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['password' => 'Mật khẩu không đúng.']);
        }

        // Đăng nhập thành công
        Auth::guard('nguoi_dung')->login($nguoiDung, $request->boolean('remember'));

        $request->session()->regenerate();

        if ($nguoiDung->vai_tro === 'khách hàng') {
            $request->session()->flash('show_voucher_popup', true);
        }

        return $this->redirectByRole($nguoiDung);
    }

    /**
     * Đăng xuất.
     */
    public function logout(Request $request)
    {
        Auth::guard('nguoi_dung')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đã đăng xuất thành công.');
    }

    /**
     * Redirect theo vai trò sau khi đăng nhập.
     */
    private function redirectByRole(NguoiDung $nguoiDung)
    {
        return match($nguoiDung->vai_tro) {
            'quản lý', 'chủ cửa hàng' => redirect()->route('manager.dashboard'),
            'nhân viên' => redirect()->route('staff.dashboard'),
            default     => redirect()->route('home'),   // khách hàng
        };
    }
}
