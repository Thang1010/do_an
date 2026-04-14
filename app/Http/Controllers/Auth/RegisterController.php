<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\HoSoKhachHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    // Mã xác thực cho nhân viên và quản lý (trong thực tế lưu vào DB/config)
    const MA_NHAN_VIEN = 'XMSTAFF2024';
    const MA_QUAN_LY   = 'XMADMIN2024';

    /**
     * Hiển thị form đăng ký.
     */
    public function showRegisterForm()
    {
        if (Auth::guard('nguoi_dung')->check()) {
            return redirect()->route('home');
        }
        return view('auth.register');
    }

    /**
     * Xử lý đăng ký tài khoản.
     */
    public function register(Request $request)
    {
        // === Quy tắc xác thực ===
        $rules = [
            'ho_ten'   => 'required|string|min:2|max:150',
            'vai_tro'  => 'required|in:khách hàng,nhân viên,quản lý',
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms'    => 'accepted',
        ];

        // Email hoặc SĐT — ít nhất 1 cái
        if (empty($request->email) && empty($request->so_dien_thoai)) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Vui lòng nhập ít nhất một trong hai: Email hoặc Số điện thoại.']);
        }

        if (!empty($request->email)) {
            $rules['email'] = 'email|max:150|unique:nguoi_dung,email';
        }
        if (!empty($request->so_dien_thoai)) {
            $rules['so_dien_thoai'] = 'digits_between:9,11|unique:nguoi_dung,so_dien_thoai';
        }

        // Mã xác thực cho nhân viên/quản lý
        if ($request->vai_tro !== 'khách hàng') {
            $rules['ma_xac_thuc'] = 'required|string';
        }

        $messages = [
            'ho_ten.required'          => 'Vui lòng nhập họ và tên.',
            'ho_ten.min'               => 'Họ tên phải có ít nhất 2 ký tự.',
            'email.email'              => 'Email không hợp lệ.',
            'email.unique'             => 'Email này đã được sử dụng.',
            'so_dien_thoai.digits_between' => 'Số điện thoại phải từ 9 đến 11 chữ số.',
            'so_dien_thoai.unique'     => 'Số điện thoại này đã được sử dụng.',
            'password.min'             => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed'       => 'Mật khẩu xác nhận không khớp.',
            'vai_tro.in'               => 'Loại tài khoản không hợp lệ.',
            'ma_xac_thuc.required'     => 'Vui lòng nhập mã xác thực.',
            'terms.accepted'           => 'Bạn phải đồng ý với điều khoản sử dụng.',
        ];

        $request->validate($rules, $messages);

        // === Kiểm tra mã xác thực ===
        if ($request->vai_tro === 'nhân viên') {
            if ($request->ma_xac_thuc !== self::MA_NHAN_VIEN) {
                return back()
                    ->withInput()
                    ->withErrors(['ma_xac_thuc' => 'Mã xác thực nhân viên không đúng.']);
            }
        }
        if ($request->vai_tro === 'quản lý') {
            if ($request->ma_xac_thuc !== self::MA_QUAN_LY) {
                return back()
                    ->withInput()
                    ->withErrors(['ma_xac_thuc' => 'Mã xác thực quản lý không đúng.']);
            }
        }

        // === Tạo tài khoản ===
        DB::beginTransaction();
        try {
            $nguoiDung = NguoiDung::create([
                'ho_ten'        => trim($request->ho_ten),
                'email'         => !empty($request->email) ? strtolower(trim($request->email)) : null,
                'so_dien_thoai' => !empty($request->so_dien_thoai) ? trim($request->so_dien_thoai) : null,
                'mat_khau'      => Hash::make($request->password),
                'vai_tro'       => $request->vai_tro,
                'trang_thai'    => 'hoạt động',
            ]);

            // Tạo hồ sơ khách hàng nếu là khách hàng
            if ($request->vai_tro === 'khách hàng') {
                HoSoKhachHang::create([
                    'nguoi_dung_id' => $nguoiDung->id,
                ]);
            }

            DB::commit();

            // Đăng nhập ngay sau khi đăng ký
            Auth::guard('nguoi_dung')->login($nguoiDung);

            session()->regenerate();

            // Redirect theo vai trò
            return match($nguoiDung->vai_tro) {
                'quản lý'   => redirect()->route('manager.dashboard')
                                         ->with('success', 'Chào mừng quản lý ' . $nguoiDung->ho_ten . '!'),
                'nhân viên' => redirect()->route('staff.dashboard')
                                         ->with('success', 'Chào mừng ' . $nguoiDung->ho_ten . '!'),
                default     => redirect()->route('home')
                                         ->with('success', 'Đăng ký thành công! Chào mừng ' . $nguoiDung->ho_ten . ' đến với XM Coffee 🎉'),
            };

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['general' => 'Đã xảy ra lỗi, vui lòng thử lại. ' . $e->getMessage()]);
        }
    }
}
