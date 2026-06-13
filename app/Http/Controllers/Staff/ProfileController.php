<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\HoSoNhanVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'ho_ten' => ['nullable', 'string', 'max:70'],
            'email' => [
                'nullable',
                'email',
                'max:60',
                Rule::unique('nguoi_dung', 'email')->ignore($user->id),
            ],
            'so_dien_thoai' => ['nullable', 'string', 'max:10'],
            'ngay_sinh' => ['nullable', 'date'],
            'dia_chi_tam_chu' => ['nullable', 'string', 'max:150'],
        ], [
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
        ]);
        $filename = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->decode($file)->cover(500, 500);

            $filename = 'avatars/' . \Illuminate\Support\Str::uuid() . '-' . time() . '.jpg';
            Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
        }

        // Update email on nguoi_dung if provided
        $emailUpdate = trim($validated['email'] ?? '');
        if ($emailUpdate !== '') {
            $user->update(['email' => $emailUpdate]);
        }

        $profileData = [
            'ho_ten' => trim($validated['ho_ten'] ?? '') ?: null,
            'so_dien_thoai' => trim($validated['so_dien_thoai'] ?? '') ?: null,
            'ngay_sinh' => $validated['ngay_sinh'] ?: null,
            'dia_chi_tam_chu' => trim($validated['dia_chi_tam_chu'] ?? '') ?: null,
        ];

        if ($filename) {
            if ($user->hoSoNhanVien?->anh_dai_dien) {
                Storage::disk('s3')->delete($user->hoSoNhanVien->anh_dai_dien);
                Storage::disk('public')->delete($user->hoSoNhanVien->anh_dai_dien);
            }
            $profileData['anh_dai_dien'] = $filename;
        }

        // Update staff profile
        HoSoNhanVien::updateOrCreate(
            ['nguoi_dung_id' => $user->id],
            $profileData
        );

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|max:20|confirmed',
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
