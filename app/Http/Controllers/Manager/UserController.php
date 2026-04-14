<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\HoSoKhachHang;
use App\Models\HoSoNhanVien;
use App\Models\HoSoQuanLy;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** Danh sách khách hàng */
    public function customers(Request $request)
    {
        $query = NguoiDung::query()
            ->with('hoSoKhachHang')
            ->withSum('donHang as tong_chi_tieu_tai_khoan', 'tong_tien')
            ->where('vai_tro', 'khách hàng');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ho_ten', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('so_dien_thoai', 'like', "%$s%");
            });
        }

        if ($request->filled('trang_thai')) {
            $map = ['hoat_dong' => 'hoạt động', 'bi_khoa' => 'bị khóa'];
            $query->where('trang_thai', $map[$request->trang_thai] ?? $request->trang_thai);
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        $totalCustomers = NguoiDung::where('vai_tro', 'khách hàng')->count();

        return view('manager.users.customers.index', compact('customers', 'totalCustomers'));
    }

    /** Danh sách nhân viên */
    public function staffs(Request $request)
    {
        $query = NguoiDung::with('hoSoNhanVien')
            ->where('vai_tro', 'nhân viên');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ho_ten', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('so_dien_thoai', 'like', "%$s%");
            });
        }

        if ($request->filled('trang_thai')) {
            $map = ['hoat_dong' => 'hoạt động', 'bi_khoa' => 'bị khóa'];
            $query->where('trang_thai', $map[$request->trang_thai] ?? $request->trang_thai);
        }

        $staffList = $query->latest()->paginate(20)->withQueryString();
        $totalStaff = NguoiDung::where('vai_tro', 'nhân viên')->count();

        return view('manager.users.staffs.index', compact('staffList', 'totalStaff'));
    }

    /** BC route name compatibility */
    public function staff(Request $request)
    {
        return $this->staffs($request);
    }

    /** Danh sách quản lý / admin */
    public function admins(Request $request)
    {
        $query = NguoiDung::query()
            ->with('hoSoQuanLy')
            ->where('vai_tro', 'quản lý');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ho_ten', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('so_dien_thoai', 'like', "%$s%");
            });
        }

        if ($request->filled('trang_thai')) {
            $map = ['hoat_dong' => 'hoạt động', 'bi_khoa' => 'bị khóa'];
            $query->where('trang_thai', $map[$request->trang_thai] ?? $request->trang_thai);
        }

        $admins = $query->latest()->paginate(20)->withQueryString();
        $totalAdmins = NguoiDung::where('vai_tro', 'quản lý')->count();

        return view('manager.users.admins.index', compact('admins', 'totalAdmins'));
    }

    /** Chi tiết người dùng theo hồ sơ role */
    public function show(int $id)
    {
        $user = NguoiDung::with([
                'hoSoKhachHang',
                'hoSoNhanVien',
                'hoSoQuanLy',
            ])
            ->findOrFail($id);

        $paidOrderQuery = $this->paidOrdersByUserQuery($user)->latest();
        $tongChiTieuTaiKhoan = $this->calculateCustomerSpending($user);
        $user->setAttribute('tong_chi_tieu_tai_khoan', $tongChiTieuTaiKhoan);

        $paidOrderCount = (clone $paidOrderQuery)->count();
        $recentPaidOrders = $paidOrderQuery->limit(10)->get();

        $viewGroup = $this->resolveUserViewGroup($user->vai_tro);

        return view("manager.users.{$viewGroup}.show", compact('user', 'paidOrderCount', 'recentPaidOrders'));
    }

    /** Khóa / Mở khóa tài khoản */
    public function toggleLock(int $id)
    {
        $user = NguoiDung::findOrFail($id);

        // Không cho khóa tài khoản quản lý
        if ($user->vai_tro === 'quản lý') {
            return back()->with('error', 'Không thể khóa tài khoản quản lý.');
        }

        $newStatus = $user->trang_thai === 'hoạt động' ? 'bị khóa' : 'hoạt động';
        $user->update(['trang_thai' => $newStatus]);

        $action = $newStatus === 'bị khóa' ? 'khóa' : 'mở khóa';
        return back()->with('success', "Đã {$action} tài khoản của {$user->ho_ten}.");
    }

    /** Xóa tài khoản người dùng */
    public function destroy(Request $request, int $id)
    {
        $user = NguoiDung::findOrFail($id);

        if ((int) Auth::id() === (int) $user->id) {
            return back()->with('error', 'Không thể xóa chính tài khoản đang đăng nhập.');
        }

        $from = $this->normalizeListOrigin($request->input('from'));
        $redirectRoute = $this->resolveListRouteByOrigin($from, $user->vai_tro);
        $name = $user->ho_ten;

        try {
            DB::transaction(function () use ($user): void {
                $user->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể xóa tài khoản do còn dữ liệu liên quan.');
        }

        return redirect()->route($redirectRoute)
            ->with('success', "Đã xóa tài khoản {$name}.");
    }

    /** Lịch sử mua hàng của khách */
    public function orderHistory(int $id)
    {
        return redirect()->route('manager.users.show', $id);
    }

    /** Trang sửa thông tin quyền tài khoản */
    public function edit(int $id)
    {
        $user = NguoiDung::with(['hoSoKhachHang', 'hoSoNhanVien', 'hoSoQuanLy'])->findOrFail($id);
        $from = $this->normalizeListOrigin(request()->query('from'));

        $roleOptions = [
            'khách hàng' => 'Khách hàng',
            'nhân viên' => 'Nhân viên',
            'quản lý' => 'Admin',
        ];

        $viewGroup = $this->resolveUserViewGroup($user->vai_tro);

        return view("manager.users.{$viewGroup}.edit", compact('user', 'roleOptions', 'from'));
    }

    /** Cập nhật vai trò tài khoản */
    public function updateRole(Request $request, int $id)
    {
        $request->validate([
            'vai_tro' => 'required|string|max:50',
            'trang_thai' => 'required|string|in:hoạt động,bị khóa,ngưng hoạt động',
            'from' => 'nullable|string|in:customers,staff,staffs,admins',
            'chuc_vu' => 'nullable|string|max:100',
            'luong_co_ban' => 'nullable|numeric|min:0',
            'ngay_vao_lam' => 'nullable|date',
            'so_tai_khoan' => 'nullable|string|max:50',
            'ngan_hang' => 'nullable|string|max:150',
        ]);

        $user = NguoiDung::findOrFail($id);
        $newRole = $this->normalizeRole($request->input('vai_tro'));
        $newStatus = $request->input('trang_thai');
        $from = $this->normalizeListOrigin($request->input('from'));

        if (! $newRole) {
            return back()->with('error', 'Vai trò không hợp lệ.');
        }

        if ((int) Auth::id() === (int) $user->id && $newStatus !== 'hoạt động') {
            return back()->with('error', 'Không thể tự chuyển tài khoản đang đăng nhập sang trạng thái không hoạt động.');
        }

        $oldRole = $user->vai_tro;
        $oldStatus = $user->trang_thai;
        $roleChanged = $oldRole !== $newRole;
        $statusChanged = $oldStatus !== $newStatus;

        DB::transaction(function () use ($request, $user, $newRole, $newStatus, $roleChanged, $statusChanged): void {
            if ($roleChanged || $statusChanged) {
                $payload = [];

                if ($roleChanged) {
                    $payload['vai_tro'] = $newRole;
                }

                if ($statusChanged) {
                    $payload['trang_thai'] = $newStatus;
                }

                $user->update($payload);
            }

            $this->ensureProfileForRole($user, $newRole);

            if ($newRole === 'nhân viên') {
                $this->updateStaffProfile($request, $user);
            }

            if ($newRole === 'quản lý') {
                $this->updateAdminProfile($request, $user);
            }
        });

        $redirectRoute = $this->resolveListRouteByOrigin($from, $oldRole);

        if ($roleChanged && $statusChanged) {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật quyền và trạng thái tài khoản của {$user->ho_ten}.");
        }

        if ($roleChanged) {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã đổi quyền {$user->ho_ten} từ {$oldRole} sang {$newRole}.");
        }

        if ($statusChanged) {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật trạng thái tài khoản của {$user->ho_ten}.");
        }

        if ($newRole === 'nhân viên') {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật thông tin nhân viên của {$user->ho_ten}.");
        }

        if ($newRole === 'quản lý') {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật thông tin quản lý của {$user->ho_ten}.");
        }

        return redirect()->route($redirectRoute)
            ->with('info', 'Không có thay đổi quyền nào được áp dụng.');
    }

    /** Thêm tài khoản khách hàng / nhân viên */
    public function store(Request $request)
    {
        $request->merge([
            'ho_ten' => trim((string) $request->input('ho_ten')),
            'email' => $this->normalizeNullable($request->input('email')),
            'so_dien_thoai' => $this->normalizeNullable($request->input('so_dien_thoai')),
            'chuc_vu' => $this->normalizeNullable($request->input('chuc_vu')),
            'ngay_vao_lam' => $this->normalizeNullable($request->input('ngay_vao_lam')),
        ]);

        $validated = $request->validate([
            'ho_ten' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:nguoi_dung,email',
            'so_dien_thoai' => 'nullable|digits_between:9,11|unique:nguoi_dung,so_dien_thoai',
            'password' => 'required|string|min:8|confirmed',
            'vai_tro' => 'required|string|max:50',
            'from' => 'nullable|string|in:customers,staff,staffs,admins',
            'chuc_vu' => 'nullable|string|max:100',
            'luong_co_ban' => 'nullable|numeric|min:0',
            'ngay_vao_lam' => 'nullable|date',
            'so_tai_khoan' => 'nullable|string|max:50',
            'ngan_hang' => 'nullable|string|max:150',
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ tên.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'so_dien_thoai.digits_between' => 'Số điện thoại phải từ 9 đến 11 chữ số.',
            'so_dien_thoai.unique' => 'Số điện thoại này đã được sử dụng.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        if (! $validated['email'] && ! $validated['so_dien_thoai']) {
            return back()
                ->withErrors(['contact' => 'Vui lòng nhập ít nhất email hoặc số điện thoại.'])
                ->withInput();
        }

        $role = $this->normalizeRole($validated['vai_tro']);
        if (! in_array($role, ['khách hàng', 'nhân viên', 'quản lý'], true)) {
            return back()->withErrors(['vai_tro' => 'Vai trò tài khoản không hợp lệ.'])->withInput();
        }

        $origin = $this->normalizeListOrigin($validated['from'] ?? null);
        $redirectRoute = match (true) {
            $origin === 'customers' || $role === 'khách hàng' => 'manager.users.customers',
            $origin === 'admins' || $role === 'quản lý' => 'manager.users.admins',
            default => 'manager.users.staffs',
        };

        $createdUser = null;

        DB::transaction(function () use ($validated, $role, $request, &$createdUser): void {
            $createdUser = NguoiDung::create([
                'ho_ten' => $validated['ho_ten'],
                'email' => $validated['email'],
                'so_dien_thoai' => $validated['so_dien_thoai'],
                'mat_khau' => Hash::make($validated['password']),
                'vai_tro' => $role,
                'trang_thai' => 'hoạt động',
            ]);

            $this->ensureProfileForRole($createdUser, $role);

            if ($role === 'nhân viên') {
                $this->updateStaffProfile($request, $createdUser);
            }

            if ($role === 'quản lý') {
                $this->updateAdminProfile($request, $createdUser);
            }
        });

        if (! $createdUser) {
            return back()->with('error', 'Không thể tạo tài khoản mới, vui lòng thử lại.');
        }

        return redirect()->route($redirectRoute)
            ->with('success', "Đã thêm tài khoản {$createdUser->ho_ten} ({$role}).");
    }

    private function paidPaymentStatuses(): array
    {
        return [
            'đã thanh toán',
            'da thanh toán',
            'đã thanh toan',
            'da thanh toan',
            'da_thanh_toan',
            'paid',
            'completed',
        ];
    }

    private function paidOrdersByUserQuery(NguoiDung $user): Builder
    {
        $query = DonHang::query();
        $this->applyPaidStatusConstraint($query);

        return $query->where('nguoi_dung_id', $user->id);
    }

    private function calculateCustomerSpending(NguoiDung $user): float
    {
        return (float) $user->donHang()->sum('tong_tien');
    }

    private function applyPaidStatusConstraint(Builder $query, string $column = 'trang_thai_thanh_toan'): Builder
    {
        $normalizedExpr = "TRIM(LOWER(REPLACE({$column}, '_', ' ')))";

        return $query->where(function ($q) use ($column, $normalizedExpr) {
            $q->whereIn($column, $this->paidPaymentStatuses())
                ->orWhereRaw("{$normalizedExpr} IN (?, ?, ?, ?, ?, ?)", [
                    'đã thanh toán',
                    'da thanh toán',
                    'đã thanh toan',
                    'da thanh toan',
                    'paid',
                    'completed',
                ]);
        });
    }

    private function normalizeRole(?string $role): ?string
    {
        if (! $role) {
            return null;
        }

        $normalized = mb_strtolower(trim($role));

        return match ($normalized) {
            'khach hang', 'khách hàng', 'customer' => 'khách hàng',
            'nhan vien', 'nhân viên', 'staff' => 'nhân viên',
            'quan ly', 'quản lý', 'admin', 'administrator' => 'quản lý',
            default => null,
        };
    }

    private function ensureProfileForRole(NguoiDung $user, string $role): void
    {
        if ($role === 'khách hàng') {
            HoSoKhachHang::firstOrCreate([
                'nguoi_dung_id' => $user->id,
            ]);

            return;
        }

        if ($role === 'nhân viên') {
            HoSoNhanVien::firstOrCreate(
                ['nguoi_dung_id' => $user->id],
                [
                    'ma_nhan_vien' => 'NV' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                    'chuc_vu' => 'Nhân viên',
                ]
            );

            return;
        }

        HoSoQuanLy::firstOrCreate(
            ['nguoi_dung_id' => $user->id],
            [
                'ma_quan_ly' => 'QL' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
            ]
        );
    }

    private function updateStaffProfile(Request $request, NguoiDung $user): void
    {
        $staffProfile = HoSoNhanVien::firstOrCreate(
            ['nguoi_dung_id' => $user->id],
            [
                'ma_nhan_vien' => 'NV' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'chuc_vu' => 'Nhân viên',
            ]
        );

        $staffProfile->update([
            'chuc_vu' => $this->normalizeNullable($request->input('chuc_vu')) ?? ($staffProfile->chuc_vu ?: 'Nhân viên'),
            'luong_co_ban' => $request->filled('luong_co_ban') ? (float) $request->input('luong_co_ban') : ($staffProfile->luong_co_ban ?? 0),
            'ngay_vao_lam' => $this->normalizeNullable($request->input('ngay_vao_lam')),
        ]);
    }

    private function updateAdminProfile(Request $request, NguoiDung $user): void
    {
        $adminProfile = HoSoQuanLy::firstOrCreate(
            ['nguoi_dung_id' => $user->id],
            [
                'ma_quan_ly' => 'QL' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
            ]
        );

        $adminProfile->update([
            'ngay_vao_lam' => $this->normalizeNullable($request->input('ngay_vao_lam')),
            'so_tai_khoan' => $this->normalizeNullable($request->input('so_tai_khoan')),
            'ngan_hang' => $this->normalizeNullable($request->input('ngan_hang')),
        ]);
    }

    private function normalizeListOrigin(?string $origin): ?string
    {
        if (! $origin) {
            return null;
        }

        $origin = trim(mb_strtolower($origin));

        return match ($origin) {
            'customers' => 'customers',
            'staff', 'staffs' => 'staffs',
            'admins' => 'admins',
            default => null,
        };
    }

    private function resolveListRouteByOrigin(?string $origin, string $fallbackRole): string
    {
        if ($origin === 'customers') {
            return 'manager.users.customers';
        }

        if ($origin === 'staffs') {
            return 'manager.users.staffs';
        }

        if ($origin === 'admins') {
            return 'manager.users.admins';
        }

        return match ($fallbackRole) {
            'nhân viên' => 'manager.users.staffs',
            'quản lý' => 'manager.users.admins',
            default => 'manager.users.customers',
        };
    }

    private function resolveUserViewGroup(string $role): string
    {
        return match ($role) {
            'nhân viên' => 'staffs',
            'quản lý' => 'admins',
            default => 'customers',
        };
    }

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
