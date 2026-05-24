<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\HoSoKhachHang;
use App\Models\HoSoNhanVien;
use App\Models\HoSoQuanLy;
use App\Models\NguoiDung;
use App\Models\PendingCustomerRegistration;
use App\Notifications\CustomerEmailVerificationNotification;
use App\Notifications\PendingAccountApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
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
        $validated = $request->validate([
            'ho_ten' => 'required|string|min:2|max:150',
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('nguoi_dung', 'email'),
                Rule::unique('tai_khoan_cho_xac_minh', 'email'),
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'vai_tro' => 'required|in:khách hàng,nhân viên,quản lý',
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ và tên.',
            'ho_ten.min' => 'Họ tên phải có ít nhất 2 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'vai_tro.in' => 'Loại tài khoản không hợp lệ.',
        ]);

        DB::beginTransaction();

        try {
            if ($validated['vai_tro'] === 'khách hàng') {
                $pending = $this->createPendingRegistration($validated);
                $request->session()->put('pending_verification_email', $pending->email);

                DB::commit();

                return redirect()
                    ->route('auth.verify-email')
                    ->with('success', 'Đã gửi mã xác minh đến email của bạn.');
            }

            $store = CuaHang::query()->orderBy('id')->first();

            $nguoiDung = NguoiDung::create([
                'cua_hang_id' => $store?->id,
                'ho_ten' => trim($validated['ho_ten']),
                'email' => strtolower(trim($validated['email'])),
                'mat_khau' => Hash::make($validated['password']),
                'vai_tro' => $validated['vai_tro'],
                'trang_thai' => 'ngưng hoạt động',
            ]);

            if ($validated['vai_tro'] === 'khách hàng') {
                HoSoKhachHang::firstOrCreate([
                    'nguoi_dung_id' => $nguoiDung->id,
                ]);
            }

            if ($validated['vai_tro'] === 'nhân viên') {
                HoSoNhanVien::firstOrCreate(
                    ['nguoi_dung_id' => $nguoiDung->id],
                    [
                        'ma_nhan_vien' => 'NV' . str_pad((string) $nguoiDung->id, 5, '0', STR_PAD_LEFT),
                        'chuc_vu_id' => null,
                        'luong_co_ban' => 0,
                    ]
                );
            }

            if ($validated['vai_tro'] === 'quản lý') {
                $managerPosition = \App\Models\ChucVu::query()->firstOrCreate(
                    ['ten_chuc_vu' => 'Quản lý'],
                    array_filter([
                        'mo_ta_chuc_vu' => 'Chức vụ dành cho tài khoản quản lý.',
                        'vai_tro_ap_dung' => Schema::hasColumn('chuc_vu', 'vai_tro_ap_dung') ? 'quản lý' : null,
                    ], static fn ($value) => $value !== null)
                );

                if (Schema::hasColumn('chuc_vu', 'vai_tro_ap_dung') && (string) $managerPosition->vai_tro_ap_dung !== 'quản lý') {
                    $managerPosition->update(['vai_tro_ap_dung' => 'quản lý']);
                }

                $managerPositionId = $managerPosition->id;

                HoSoQuanLy::firstOrCreate(
                    ['nguoi_dung_id' => $nguoiDung->id],
                    [
                        'cua_hang_id' => $store?->id,
                        'chuc_vu_id' => $managerPositionId,
                        'ma_quan_ly' => 'QL' . str_pad((string) $nguoiDung->id, 5, '0', STR_PAD_LEFT),
                    ]
                );
            }

            DB::commit();

            $this->notifyApproversForApproval($nguoiDung, $store);

            return redirect()
                ->route('auth.login')
                ->with('success', 'Đăng ký thành công. Tài khoản của bạn đang chờ quản trị hệ thống xác nhận.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Đăng ký tài khoản thất bại.', [
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'Đã xảy ra lỗi, vui lòng thử lại.']);
        }
    }

    private function notifyApproversForApproval(NguoiDung $pendingUser, ?CuaHang $store): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        try {
            $approvalRoles = $this->approvalRolesFor($pendingUser->vai_tro);
            if ($approvalRoles === []) {
                return;
            }

            $approvers = NguoiDung::query()
                ->whereIn('vai_tro', $approvalRoles)
                ->where('trang_thai', 'hoạt động')
                ->when($store?->id || $store?->chu_cua_hang_id, function ($q) use ($store) {
                    $q->where(function ($scope) use ($store) {
                        if ($store?->id) {
                            $scope->where('cua_hang_id', $store->id);
                        }

                        if ($store?->chu_cua_hang_id) {
                            $scope->orWhere('id', $store->chu_cua_hang_id);
                        }
                    });
                })
                ->get();

            if ($approvers->isEmpty()) {
                return;
            }

            $approvers->each->notify(new PendingAccountApprovalNotification($pendingUser, $store));
        } catch (\Throwable $e) {
            Log::warning('Không thể gửi thông báo đăng ký mới cho nhóm xác nhận tài khoản.', [
                'pending_user_id' => $pendingUser->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function approvalRolesFor(string $pendingRole): array
    {
        return match ($pendingRole) {
            'khách hàng' => ['chủ cửa hàng', 'quản lý', 'nhân viên'],
            'nhân viên' => ['chủ cửa hàng', 'quản lý'],
            'quản lý' => ['chủ cửa hàng'],
            default => ['chủ cửa hàng'],
        };
    }

    private function createPendingRegistration(array $validated): PendingCustomerRegistration
    {
        $code = (string) random_int(100000, 999999);

        $pending = PendingCustomerRegistration::updateOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            [
                'ho_ten' => trim($validated['ho_ten']),
                'mat_khau_ma_hoa' => Hash::make($validated['password']),
                'ma_xac_minh_ma_hoa' => Hash::make($code),
                'het_han_luc' => now()->addMinutes(10),
            ]
        );

        try {
            Notification::route('mail', $pending->email)
                ->notify(new CustomerEmailVerificationNotification($code));
        } catch (\Throwable $e) {
            Log::warning('Không thể gửi mã xác minh email khi đăng ký.', [
                'email' => $pending->email,
                'error' => $e->getMessage(),
            ]);
        }

        return $pending;
    }
}
