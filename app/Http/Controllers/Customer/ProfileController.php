<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HoSoKhachHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->loadMissing(['hoSoKhachHang']);
        $profile = $user->hoSoKhachHang;

        return view('customer.profile.edit', compact('user', 'profile'));
    }

    public function editPassword(Request $request)
    {
        return view('customer.profile.password');
    }

    public function update(Request $request)
    {
        $user = $request->user()->loadMissing(['hoSoKhachHang']);

        $validated = $request->validate([
            'ho_ten'        => ['nullable', 'string', 'max:70'],
            'email'         => ['nullable', 'email', 'max:60', Rule::unique('nguoi_dung', 'email')->ignore($user->id)],
            'gioi_tinh'     => ['nullable', Rule::in(['nam', 'nữ'])],
            'ngay_sinh'     => ['nullable', 'date', 'before:today'],
            'dia_chi'       => ['nullable', 'string', 'max:500'],
            'avatar'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [
            'email.email'            => 'Email không hợp lệ.',
            'email.unique'           => 'Email đã được sử dụng.',
            'avatar.image'           => 'File phải là ảnh.',
            'avatar.max'             => 'Ảnh không được vượt quá 2MB.',
        ]);

        // Update email on nguoi_dung if provided
        $emailUpdate = trim($validated['email'] ?? '');
        if ($emailUpdate !== '') {
            $user->update(['email' => $emailUpdate]);
        }

        $profileData = [
            'ho_ten'    => trim($validated['ho_ten'] ?? '') ?: null,
            'gioi_tinh' => $validated['gioi_tinh'] ?: null,
            'ngay_sinh' => $validated['ngay_sinh'] ?: null,
            'dia_chi'   => $validated['dia_chi'] ?: null,
        ];

        if ($request->hasFile('avatar')) {
            $oldPath = $user->hoSoKhachHang?->anh_dai_dien;
            if ($oldPath) {
                Storage::disk('s3')->delete($oldPath);
                Storage::disk('public')->delete($oldPath);
            }
            $file = $request->file('avatar');
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->decode($file)->cover(500, 500);

            $filename = 'avatars/' . \Illuminate\Support\Str::uuid() . '-' . time() . '.jpg';
            Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
            $profileData['anh_dai_dien'] = $filename;
        }

        HoSoKhachHang::updateOrCreate(
            ['nguoi_dung_id' => $user->id],
            $profileData
        );

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|max:20|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required'     => 'Vui lòng nhập mật khẩu mới.',
            'new_password.confirmed'    => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->mat_khau)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.'])->withFragment('password');
        }

        $user->update(['mat_khau' => $request->new_password]);

        return back()->with('success', 'Đổi mật khẩu thành công!')->withFragment('password');
    }
}
