<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ChucVu;
use App\Models\CuaHang;
use App\Models\HoSoKhachHang;
use App\Models\HoSoNhanVien;
use App\Models\HoSoQuanLy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private function defaultManagerPositionId(): ?int
    {
        return ChucVu::query()->firstOrCreate(
            ['ten_chuc_vu' => 'Quản lý'],
            ['mo_ta_chuc_vu' => 'Chức vụ dành cho tài khoản quản lý.']
        )->id;
    }

    private function managerBankOptions(): array
    {
        return [
            'MBBank' => 'MBBank',
        ];
    }

    private function normalizeNullable(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    public function edit(Request $request)
    {
        $user = $request->user()->loadMissing(['cuaHang', 'hoSoQuanLy.chucVu', 'hoSoNhanVien.chucVu', 'hoSoKhachHang']);
        $managerBankOptions = $this->managerBankOptions();
        $positions = ChucVu::query()->orderBy('ten_chuc_vu')->get();

        return view('manager.profile.edit', compact('user', 'managerBankOptions', 'positions'));
    }

    public function update(Request $request)
    {
        $user = $request->user()->loadMissing(['hoSoQuanLy', 'hoSoNhanVien', 'hoSoKhachHang']);

        $rules = [
            'ho_ten' => ['nullable', 'string', 'max:70'],
            'email' => [
                'nullable',
                'email',
                'max:60',
                Rule::unique('nguoi_dung', 'email')->ignore($user->id),
            ],
        ];

        if ($user->vai_tro === 'quản lý') {
            $rules = array_merge($rules, [
                'so_dien_thoai' => ['nullable', 'string', 'max:20'],
                'ngay_sinh' => ['nullable', 'date'],
                'dia_chi_tam_chu' => ['nullable', 'string', 'max:255'],
            ]);
        } elseif ($user->vai_tro === 'chủ cửa hàng') {
            $rules['email'] = [
                'required',
                'email',
                'max:60',
                Rule::unique('nguoi_dung', 'email')->ignore($user->id),
            ];
            $rules = array_merge($rules, [
                'so_dien_thoai' => ['nullable', 'string', 'max:20'],
                'cua_hang_so_dien_thoai' => ['nullable', 'string', 'max:20'],
                'cua_hang_dia_chi' => ['nullable', 'string', 'max:255'],
                'cua_hang_lien_ket_trang' => ['nullable', 'string', 'max:255'],
                'cua_hang_mo_ta' => ['nullable', 'string'],
            ]);
        } elseif ($user->vai_tro === 'nhân viên') {
            $rules = array_merge($rules, [
                'chuc_vu_id' => ['nullable', 'integer', 'exists:chuc_vu,id'],
                'so_dien_thoai' => ['nullable', 'string', 'max:10'],
                'ngay_sinh' => ['nullable', 'date'],
                'dia_chi_tam_chu' => ['nullable', 'string', 'max:150'],
                'ngay_vao_lam' => ['nullable', 'date'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'gioi_tinh' => ['nullable', Rule::in(['nam', 'nữ'])],
                'ngay_sinh' => ['nullable', 'date'],
                'dia_chi' => ['nullable', 'string'],
            ]);
        }

        $validated = $request->validate($rules, [
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
        ]);

        $filename = null;
        if ($request->hasFile('avatar') && $user->vai_tro !== 'chủ cửa hàng') {
            $file = $request->file('avatar');
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->decode($file)->cover(500, 500);

            $filename = 'avatars/' . \Illuminate\Support\Str::uuid() . '-' . time() . '.jpg';
            Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
        }

        DB::transaction(function () use ($user, $validated, $filename) {
            $emailUpdate = $this->normalizeNullable($validated['email'] ?? null);
            if ($emailUpdate) {
                $user->update(['email' => $emailUpdate]);
            }

            if ($user->vai_tro === 'quản lý') {
                $profileData = [
                    'ho_ten' => $this->normalizeNullable($validated['ho_ten'] ?? null),
                    'so_dien_thoai' => $this->normalizeNullable($validated['so_dien_thoai'] ?? null),
                    'ngay_sinh' => $this->normalizeNullable($validated['ngay_sinh'] ?? null),
                    'dia_chi_tam_chu' => $this->normalizeNullable($validated['dia_chi_tam_chu'] ?? null),
                    'chuc_vu_id' => $user->hoSoQuanLy?->chuc_vu_id ?? $this->defaultManagerPositionId(),
                    'ngay_vao_lam' => $user->hoSoQuanLy?->ngay_vao_lam,
                ];

                if ($filename) {
                    if ($user->hoSoQuanLy?->anh_dai_dien) {
                        Storage::disk('s3')->delete($user->hoSoQuanLy->anh_dai_dien);
                        Storage::disk('public')->delete($user->hoSoQuanLy->anh_dai_dien);
                    }
                    $profileData['anh_dai_dien'] = $filename;
                }

                HoSoQuanLy::updateOrCreate(
                    ['nguoi_dung_id' => $user->id],
                    $profileData
                );

                return;
            }

            if ($user->vai_tro === 'chủ cửa hàng') {
                HoSoQuanLy::updateOrCreate(
                    ['nguoi_dung_id' => $user->id],
                    [
                        'ho_ten' => $this->normalizeNullable($validated['ho_ten'] ?? null),
                        'so_dien_thoai' => $this->normalizeNullable($validated['so_dien_thoai'] ?? null),
                        'chuc_vu_id' => $user->hoSoQuanLy?->chuc_vu_id ?? $this->defaultManagerPositionId(),
                    ]
                );

                $store = CuaHang::query()
                    ->when($user->cua_hang_id, fn($q) => $q->where('id', $user->cua_hang_id))
                    ->when(!$user->cua_hang_id, fn($q) => $q->whereHas('chuCuaHang', fn($sq) => $sq->where('id', $user->id)))
                    ->first() ?? CuaHang::first();

                if ($store) {
                    $store->update([
                        'so_dien_thoai' => $this->normalizeNullable($validated['cua_hang_so_dien_thoai'] ?? null),
                        'dia_chi' => $this->normalizeNullable($validated['cua_hang_dia_chi'] ?? null),
                        'lien_ket_trang' => $this->normalizeNullable($validated['cua_hang_lien_ket_trang'] ?? null),
                        'mo_ta' => $this->normalizeNullable($validated['cua_hang_mo_ta'] ?? null),
                    ]);

                    if (!$user->cua_hang_id) {
                        $user->update(['cua_hang_id' => $store->id]);
                    }
                }

                return;
            }

            if ($user->vai_tro === 'nhân viên') {
                $profileData = [
                    'ho_ten' => $this->normalizeNullable($validated['ho_ten'] ?? null),
                    'chuc_vu_id' => $validated['chuc_vu_id']
                        ?? $user->hoSoNhanVien?->chuc_vu_id
                        ?? null,
                    'so_dien_thoai' => $this->normalizeNullable($validated['so_dien_thoai'] ?? null),
                    'ngay_sinh' => $this->normalizeNullable($validated['ngay_sinh'] ?? null),
                    'dia_chi_tam_chu' => $this->normalizeNullable($validated['dia_chi_tam_chu'] ?? null),
                    'ngay_vao_lam' => $this->normalizeNullable($validated['ngay_vao_lam'] ?? null),
                ];

                if ($filename) {
                    if ($user->hoSoNhanVien?->anh_dai_dien) {
                        Storage::disk('s3')->delete($user->hoSoNhanVien->anh_dai_dien);
                        Storage::disk('public')->delete($user->hoSoNhanVien->anh_dai_dien);
                    }
                    $profileData['anh_dai_dien'] = $filename;
                }

                HoSoNhanVien::updateOrCreate(
                    ['nguoi_dung_id' => $user->id],
                    $profileData
                );

                return;
            }

            $profileData = [
                'ho_ten' => $this->normalizeNullable($validated['ho_ten'] ?? null),
                'gioi_tinh' => $this->normalizeNullable($validated['gioi_tinh'] ?? null),
                'ngay_sinh' => $this->normalizeNullable($validated['ngay_sinh'] ?? null),
                'dia_chi' => $this->normalizeNullable($validated['dia_chi'] ?? null),
            ];

            if ($filename) {
                if ($user->hoSoKhachHang?->anh_dai_dien) {
                    Storage::disk('s3')->delete($user->hoSoKhachHang->anh_dai_dien);
                    Storage::disk('public')->delete($user->hoSoKhachHang->anh_dai_dien);
                }
                $profileData['anh_dai_dien'] = $filename;
            }

            HoSoKhachHang::updateOrCreate(
                ['nguoi_dung_id' => $user->id],
                $profileData
            );
        });

        return redirect()->route('manager.profile.edit')->with('success', 'Cập nhật hồ sơ thành công.');
    }

}
