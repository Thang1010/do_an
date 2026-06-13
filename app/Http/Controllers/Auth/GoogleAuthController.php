<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\HoSoKhachHang;
use App\Models\HoSoNhanVien;
use App\Models\HoSoQuanLy;
use App\Models\NguoiDung;
use App\Notifications\PendingAccountApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{

    public function redirect(Request $request)
    {
        if ($request->filled('intent')) {
            $request->session()->put('google_auth_intent', $request->string('intent')->toString());
        }

        if ($request->filled('role')) {
            $request->session()->put('google_auth_role', $request->string('role')->toString());
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->user();
        } catch (\Throwable $e) {
            Log::warning('Google auth failed.', ['error' => $e->getMessage()]);

            return redirect()
                ->route('auth.login')
                ->withErrors(['login' => 'Không thể đăng nhập bằng Google. Vui lòng thử lại. Lỗi: ' . $e->getMessage()]);
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return redirect()
                ->route('auth.login')
                ->withErrors(['login' => 'Tài khoản Google của bạn không cung cấp email.']);
        }

        $googleId = $googleUser->getId();

        $nguoiDung = NguoiDung::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $nguoiDung) {
            $role = $this->resolveRole($request);

            try {
                $nguoiDung = $this->createFromGoogle($googleUser, $role);
            } catch (\Throwable $e) {
                Log::error('Đăng ký bằng Google thất bại.', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('auth.login')
                    ->withErrors(['login' => 'Đã xảy ra lỗi khi đăng ký bằng Google. Vui lòng thử lại.']);
            }

            if ($nguoiDung->trang_thai !== 'hoạt động' && $nguoiDung->vai_tro !== 'khách hàng') {
                return redirect()
                    ->route('auth.login')
                    ->with('success', 'Đăng ký thành công. Tài khoản của bạn đang chờ quản trị hệ thống xác nhận.');
            }
        } else {
            $dirty = false;

            if (! $nguoiDung->google_id) {
                $nguoiDung->google_id = $googleId;
                $dirty = true;
            }

            if (! $nguoiDung->email) {
                $nguoiDung->email = strtolower($email);
                $dirty = true;
            }

            if (! $nguoiDung->email_da_xac_thuc_luc) {
                $nguoiDung->email_da_xac_thuc_luc = now();
                $dirty = true;
            }

            if ($nguoiDung->vai_tro === 'khách hàng' && $nguoiDung->trang_thai !== 'hoạt động') {
                $nguoiDung->trang_thai = 'hoạt động';
                $dirty = true;
            }

            if ($dirty) {
                $nguoiDung->save();
            }
        }

        return $this->loginIfActive($nguoiDung, $request);
    }

    private function resolveRole(Request $request): string
    {
        $role = $request->session()->pull('google_auth_role', 'khách hàng');

        return in_array($role, ['khách hàng', 'nhân viên', 'quản lý'], true)
            ? $role
            : 'khách hàng';
    }

    private function createFromGoogle($googleUser, string $role): NguoiDung
    {
        DB::beginTransaction();

        try {
            $store = CuaHang::query()->orderBy('id')->first();

            $googleName = trim($googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User');

            $nguoiDung = NguoiDung::create([
                'cua_hang_id' => $store?->id,
                'email' => strtolower($googleUser->getEmail()),
                'mat_khau' => null,
                'vai_tro' => $role,
                'trang_thai' => $role === 'khách hàng' ? 'hoạt động' : 'ngưng hoạt động',
                'email_da_xac_thuc_luc' => now(),
                'google_id' => $googleUser->getId(),
            ]);

            if ($role === 'khách hàng') {
                HoSoKhachHang::firstOrCreate(
                    ['nguoi_dung_id' => $nguoiDung->id],
                    ['ho_ten' => $googleName]
                );
            }

            if ($role === 'nhân viên') {
                HoSoNhanVien::firstOrCreate(
                    ['nguoi_dung_id' => $nguoiDung->id],
                    [
                        'ho_ten' => $googleName,
                        'chuc_vu_id' => null,
                    ]
                );
            }

            if ($role === 'quản lý') {
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
                    ]
                );
            }

            DB::commit();

            if ($role !== 'khách hàng') {
                $this->notifyApproversForApproval($nguoiDung, $store);
            }

            return $nguoiDung;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function loginIfActive(NguoiDung $nguoiDung, Request $request)
    {
        if ($nguoiDung->trang_thai === 'ngưng hoạt động' && $nguoiDung->vai_tro !== 'khách hàng') {
            return redirect()
                ->route('auth.login')
                ->withErrors(['login' => 'Tài khoản chưa được kích hoạt. Vui lòng liên hệ quản lý.']);
        }

        if ($nguoiDung->trang_thai === 'ngưng hoạt động') {
            return redirect()
                ->route('auth.login')
                ->withErrors(['login' => 'Tài khoản chưa được kích hoạt.']);
        }

        Auth::guard('nguoi_dung')->login($nguoiDung, true);
        $request->session()->regenerate();

        if (! $nguoiDung->mat_khau) {
            $request->session()->put('force_password_setup', true);
        }

        if ($nguoiDung->vai_tro === 'khách hàng') {
            $request->session()->flash('show_voucher_popup', true);
        }

        return $this->redirectByRole($nguoiDung);
    }

    private function redirectByRole(NguoiDung $nguoiDung)
    {
        return match ($nguoiDung->vai_tro) {
            'quản lý', 'chủ cửa hàng' => redirect()->route('manager.dashboard'),
            'nhân viên' => redirect()->route('staff.dashboard'),
            default => redirect()->route('home'),
        };
    }

    private function notifyApproversForApproval(NguoiDung $pendingUser, ?CuaHang $store): void
    {
        if (! Schema::hasTable('thong_bao')) {
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
                ->when($store?->id, function ($q) use ($store) {
                    $q->where('cua_hang_id', $store->id);
                })
                ->get();

            if ($approvers->isEmpty()) {
                return;
            }

            $approvers->each->notify(new PendingAccountApprovalNotification($pendingUser, $store));
        } catch (\Throwable $e) {
            Log::warning('Không thể gửi thông báo đăng ký Google mới cho nhóm xác nhận tài khoản.', [
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
}
