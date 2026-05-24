<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('staff.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'ho_ten' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('nguoi_dung', 'email')->ignore($user->id),
            ],
            'so_dien_thoai' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('nguoi_dung', 'so_dien_thoai')->ignore($user->id),
            ],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ tên.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'so_dien_thoai.unique' => 'Số điện thoại đã được sử dụng.',
        ]);

        $user->update([
            'ho_ten' => trim($validated['ho_ten']),
            'email' => $validated['email'] ?: null,
            'so_dien_thoai' => $validated['so_dien_thoai'] ?: null,
        ]);

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->mat_khau)) {
            return back()->with('error', 'Mật khẩu hiện tại không đúng.');
        }

        $user->update(['mat_khau' => $request->new_password]);

        return back()->with('success', 'Đổi mật khẩu thành công!');
    }
}
