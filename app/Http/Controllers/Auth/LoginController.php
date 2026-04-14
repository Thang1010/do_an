<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Services\VoucherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function __construct(private readonly VoucherAssignmentService $voucherAssignmentService)
    {
    }

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
                               ->orWhere('so_dien_thoai', $loginValue)
                               ->first();

        if (! $nguoiDung) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Tài khoản không tồn tại trong hệ thống.']);
        }

        // Kiểm tra trạng thái tài khoản
        if ($nguoiDung->trang_thai === 'bị khóa') {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản lý.']);
        }

        if ($nguoiDung->trang_thai === 'ngưng hoạt động') {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Tài khoản chưa được kích hoạt.']);
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

        try {
            $assignedVouchers = $this->voucherAssignmentService->assignLoginEligibleVouchers($nguoiDung);
            if ($assignedVouchers->count() > 0) {
                $request->session()->flash('success', 'Bạn vừa nhận được ' . $assignedVouchers->count() . ' voucher mới trong tài khoản.');
            }
        } catch (\Throwable $e) {
            Log::warning('Khong the cap voucher tu dong khi dang nhap.', [
                'user_id' => $nguoiDung->id,
                'error' => $e->getMessage(),
            ]);
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
            'quản lý'   => redirect()->route('manager.dashboard'),
            'nhân viên' => redirect()->route('staff.dashboard'),
            default     => redirect()->route('home'),   // khách hàng
        };
    }
}
