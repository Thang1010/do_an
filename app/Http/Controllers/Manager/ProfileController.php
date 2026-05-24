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
            'VCB' => 'Vietcombank',
            'BIDV' => 'BIDV',
            'ICB' => 'VietinBank',
            'VBA' => 'Agribank',
            'TCB' => 'Techcombank',
            'MB' => 'MB Bank',
            'ACB' => 'ACB',
            'STB' => 'Sacombank',
            'VPB' => 'VPBank',
            'PVCB' => 'PVcomBank',
            'TPB' => 'TPBank',
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
        ];

        if ($user->vai_tro === 'quản lý') {
            $rules = array_merge($rules, [
                'chuc_vu_id' => ['nullable', 'integer', 'exists:chuc_vu,id'],
                'ngay_vao_lam' => ['nullable', 'date'],
            ]);
        } elseif ($user->vai_tro === 'chủ cửa hàng') {
            $rules = array_merge($rules, [
                'so_tai_khoan' => ['nullable', 'string', 'max:50'],
                'ngan_hang' => ['nullable', Rule::in(array_keys($this->managerBankOptions()))],
            ]);
        } elseif ($user->vai_tro === 'nhân viên') {
            $rules = array_merge($rules, [
                'ma_nhan_vien' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('ho_so_nhan_vien', 'ma_nhan_vien')->ignore($user->hoSoNhanVien?->id),
                ],
                'chuc_vu_id' => ['nullable', 'integer', 'exists:chuc_vu,id'],
                'luong_co_ban' => ['nullable', 'numeric', 'min:0'],
                'ngay_vao_lam' => ['nullable', 'date'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'gioi_tinh' => ['nullable', Rule::in(['nam', 'nữ', 'khác'])],
                'ngay_sinh' => ['nullable', 'date'],
                'dia_chi' => ['nullable', 'string'],
            ]);
        }

        $validated = $request->validate($rules, [
            'ho_ten.required' => 'Vui lòng nhập họ tên.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'so_dien_thoai.unique' => 'Số điện thoại đã được sử dụng.',
            'ma_nhan_vien.unique' => 'Mã nhân viên đã tồn tại.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'ho_ten' => trim($validated['ho_ten']),
                'email' => $this->normalizeNullable($validated['email'] ?? null),
                'so_dien_thoai' => $this->normalizeNullable($validated['so_dien_thoai'] ?? null),
            ]);

            if ($user->vai_tro === 'quản lý') {
                $managerCode = $user->hoSoQuanLy?->ma_quan_ly ?: ('QL' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT));

                HoSoQuanLy::updateOrCreate(
                    ['nguoi_dung_id' => $user->id],
                    [
                        'chuc_vu_id' => $validated['chuc_vu_id']
                            ?? $user->hoSoQuanLy?->chuc_vu_id
                            ?? $this->defaultManagerPositionId(),
                        'ma_quan_ly' => $managerCode,
                        'ngay_vao_lam' => $this->normalizeNullable($validated['ngay_vao_lam'] ?? null),
                    ]
                );

                return;
            }

            if ($user->vai_tro === 'chủ cửa hàng') {
                $store = CuaHang::query()
                    ->when($user->cua_hang_id, fn($q) => $q->where('id', $user->cua_hang_id))
                    ->when(!$user->cua_hang_id, fn($q) => $q->where('chu_cua_hang_id', $user->id))
                    ->first() ?? CuaHang::first();

                if ($store) {
                    $store->update([
                        'chu_cua_hang_id' => $user->id,
                        'so_tai_khoan' => $this->normalizeNullable($validated['so_tai_khoan'] ?? null),
                        'ngan_hang' => $this->normalizeNullable($validated['ngan_hang'] ?? null),
                    ]);

                    if (!$user->cua_hang_id) {
                        $user->update(['cua_hang_id' => $store->id]);
                    }
                }

                return;
            }

            if ($user->vai_tro === 'nhân viên') {
                $staffCode = $this->normalizeNullable($validated['ma_nhan_vien'] ?? null)
                    ?? ($user->hoSoNhanVien?->ma_nhan_vien ?: ('NV' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT)));

                HoSoNhanVien::updateOrCreate(
                    ['nguoi_dung_id' => $user->id],
                    [
                        'ma_nhan_vien' => $staffCode,
                        'chuc_vu_id' => $validated['chuc_vu_id']
                            ?? $user->hoSoNhanVien?->chuc_vu_id
                            ?? null,
                        'luong_co_ban' => (float) ($validated['luong_co_ban'] ?? 0),
                        'ngay_vao_lam' => $this->normalizeNullable($validated['ngay_vao_lam'] ?? null),
                    ]
                );

                return;
            }

            HoSoKhachHang::updateOrCreate(
                ['nguoi_dung_id' => $user->id],
                [
                    'gioi_tinh' => $this->normalizeNullable($validated['gioi_tinh'] ?? null),
                    'ngay_sinh' => $this->normalizeNullable($validated['ngay_sinh'] ?? null),
                    'dia_chi' => $this->normalizeNullable($validated['dia_chi'] ?? null),
                ]
            );
        });

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }
}
