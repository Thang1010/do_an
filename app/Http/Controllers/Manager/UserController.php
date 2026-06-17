<?php

namespace App\Http\Controllers\Manager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreUserRequest;
use App\Http\Requests\Manager\UpdateUserRoleRequest;
use App\Models\ChucVu;
use App\Models\ChiTietDonHang;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /** Danh sách khách hàng */
    public function customers(Request $request)
    {
        $query = NguoiDung::query()
            ->with('hoSoKhachHang')

            ->where('vai_tro', 'khách hàng');

        $this->applyStoreScope($query);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('email', 'like', "%$s%");
            });
        }

        if ($request->filled('trang_thai')) {
            $map = ['hoat_dong' => 'hoạt động', 'ngung_hoat_dong' => 'ngưng hoạt động'];
            $query->where('trang_thai', $map[$request->trang_thai] ?? $request->trang_thai);
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        $totalCustomersQuery = NguoiDung::query()->where('vai_tro', 'khách hàng');
        $this->applyStoreScope($totalCustomersQuery);
        $totalCustomers = $totalCustomersQuery->count();

        return view('manager.users.customers.index', compact('customers', 'totalCustomers'));
    }

    /** Danh sách nhân viên */
    public function staffs(Request $request)
    {
        $query = NguoiDung::with('hoSoNhanVien.chucVu')
            ->where('vai_tro', 'nhân viên');

        $this->applyStoreScope($query);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('email', 'like', "%$s%");
            });
        }

        if ($request->filled('trang_thai')) {
            $map = ['hoat_dong' => 'hoạt động', 'ngung_hoat_dong' => 'ngưng hoạt động'];
            $query->where('trang_thai', $map[$request->trang_thai] ?? $request->trang_thai);
        }

        $staffList = $query->latest()->paginate(20)->withQueryString();
        $totalStaffQuery = NguoiDung::query()->where('vai_tro', 'nhân viên');
        $this->applyStoreScope($totalStaffQuery);
        $totalStaff = $totalStaffQuery->count();
        $positions = $this->positionsForRole('nhân viên');

        return view('manager.users.staffs.index', compact('staffList', 'totalStaff', 'positions'));
    }

    /** BC route name compatibility */
    public function staff(Request $request)
    {
        return $this->staffs($request);
    }

    /** Danh sách quản lý / admin */
    public function admins(Request $request)
    {
        if (!$this->actorCanManageAdmins()) {
            abort(403, 'Bạn không có quyền truy cập danh sách quản lý.');
        }

        $query = NguoiDung::query()
            ->with('hoSoQuanLy.chucVu')
            ->whereIn('vai_tro', ['quản lý', 'chủ cửa hàng']);

        $this->applyStoreScope($query);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('email', 'like', "%$s%");
            });
        }

        if ($request->filled('trang_thai')) {
            $map = ['hoat_dong' => 'hoạt động', 'ngung_hoat_dong' => 'ngưng hoạt động'];
            $query->where('trang_thai', $map[$request->trang_thai] ?? $request->trang_thai);
        }

        $admins = $query->orderByRaw("CASE WHEN vai_tro = 'chủ cửa hàng' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $totalAdminsQuery = NguoiDung::query()->whereIn('vai_tro', ['quản lý', 'chủ cửa hàng']);
        $this->applyStoreScope($totalAdminsQuery);
        $totalAdmins = $totalAdminsQuery->count();
        $positions = $this->positionsForRole('quản lý');

        return view('manager.users.admins.index', compact('admins', 'totalAdmins', 'positions'));
    }

    /** Chi tiết người dùng theo hồ sơ role */
    public function show(int $id)
    {
        $user = NguoiDung::with([
            'hoSoKhachHang',
            'hoSoNhanVien.chucVu',
            'hoSoQuanLy.chucVu',
            'cuaHang',
        ])
            ->findOrFail($id);

        $this->ensureUserInCurrentStore($user);
        $this->ensureCanManageTargetUser($user);

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

        $this->ensureUserInCurrentStore($user);
        $this->ensureCanManageTargetUser($user);

        if ($user->vai_tro === 'chủ cửa hàng' && $user->trang_thai === 'hoạt động') {
            $activeOwnerCount = \App\Models\NguoiDung::where('vai_tro', 'chủ cửa hàng')->where('trang_thai', 'hoạt động')->count();
            if ($activeOwnerCount <= 1) {
                return back()->with('error', 'Phải có ít nhất 1 tài khoản chủ cửa hàng đang hoạt động. Không thể khóa tài khoản này.');
            }
        }

        $newStatus = $user->trang_thai === 'hoạt động' ? 'ngưng hoạt động' : 'hoạt động';
        $user->update(['trang_thai' => $newStatus]);

        $action = $newStatus === 'ngưng hoạt động' ? 'khóa' : 'mở khóa';
        $redirectRoute = $this->resolveListRouteByOrigin($request->input('from'), $user->vai_tro);
        return redirect()->route($redirectRoute)->with('success', "Đã {$action} tài khoản {$user->email}.");
    }

    /** Xóa tài khoản người dùng */
    public function destroy(Request $request, int $id)
    {
        $user = NguoiDung::findOrFail($id);

        $this->ensureUserInCurrentStore($user);
        $this->ensureCanManageTargetUser($user);

        if ($user->vai_tro === 'chủ cửa hàng') {
            $ownerCount = \App\Models\NguoiDung::where('vai_tro', 'chủ cửa hàng')->count();
            if ($ownerCount <= 1) {
                return back()->with('error', 'Phải có ít nhất 1 tài khoản chủ cửa hàng trong hệ thống. Không thể xóa.');
            }
        }

        if ((int) Auth::id() === (int) $user->id) {
            return back()->with('error', 'Không thể xóa chính tài khoản đang đăng nhập.');
        }

        $from = $this->normalizeListOrigin($request->input('from'));
        $redirectRoute = $this->resolveListRouteByOrigin($from, $user->vai_tro);
        $name = $user->ho_ten ?? $user->email;

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

    /** Xác nhận tài khoản đăng ký mới */
    public function confirmAccount(Request $request, int $id)
    {
        $targetUser = NguoiDung::findOrFail($id);

        $this->ensureUserInCurrentStore($targetUser);

        if (!$this->canConfirmRole($targetUser->vai_tro)) {
            return back()->with('error', 'Bạn không có quyền xác nhận tài khoản với vai trò này.');
        }

        if ($targetUser->trang_thai === 'hoạt động') {
            return back()->with('info', 'Tài khoản này đã được xác nhận trước đó.');
        }

        if ($targetUser->trang_thai !== 'ngưng hoạt động') {
            return back()->with('error', 'Chỉ có thể xác nhận tài khoản đang ở trạng thái chờ xác nhận.');
        }

        $targetUser->update([
            'trang_thai' => 'hoạt động',
        ]);

        return redirect()->route('manager.users.pending-approvals')->with('success', "Đã xác nhận tài khoản {$targetUser->email} ({$targetUser->vai_tro}).");
    }

    /** Danh sách tài khoản chờ xác nhận */
    public function pendingApprovals(Request $request)
    {
        $query = $this->buildPendingApprovalQuery($request);
        $pendingUsers = $query->latest('created_at')->paginate(20)->withQueryString();

        $pendingCount = (clone $this->buildPendingApprovalQuery($request))->count();

        return view('manager.users.pending-approvals', [
            'pendingUsers' => $pendingUsers,
            'pendingCount' => $pendingCount,
            'search' => trim((string) $request->input('search', '')),
            'requestedRole' => trim((string) $request->input('requested_role', '')),
        ]);
    }

    /** Xác nhận hàng loạt tài khoản chờ duyệt */
    public function bulkConfirmAccounts(Request $request)
    {
        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer',
            'confirm_scope' => 'nullable|in:selected,all',
            'search' => 'nullable|string|max:150',
            'requested_role' => 'nullable|string|max:50',
        ]);

        $baseQuery = $this->buildPendingApprovalQuery($request);
        $scope = (string) $request->input('confirm_scope', 'selected');

        $idsToConfirm = collect();
        if ($scope === 'all') {
            $idsToConfirm = (clone $baseQuery)->pluck('id');
        } else {
            $selectedIds = collect($request->input('user_ids', []))
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values();

            if ($selectedIds->isEmpty()) {
                return back()->with('error', 'Vui lòng chọn ít nhất một tài khoản để xác nhận.');
            }

            $idsToConfirm = (clone $baseQuery)
                ->whereIn('id', $selectedIds)
                ->pluck('id');
        }

        if ($idsToConfirm->isEmpty()) {
            return back()->with('info', 'Không có tài khoản hợp lệ để xác nhận trong danh sách hiện tại.');
        }

        $updatedCount = NguoiDung::query()
            ->whereIn('id', $idsToConfirm)
            ->where('trang_thai', 'ngưng hoạt động')
            ->update(['trang_thai' => 'hoạt động']);

        return redirect()->route('manager.users.pending-approvals', [
            'search' => $request->input('search'),
            'requested_role' => $request->input('requested_role'),
        ])->with('success', "Đã xác nhận {$updatedCount} tài khoản chờ duyệt.");
    }

    public function orderHistory(int $id)
    {
        return redirect()->route('manager.users.customers');
    }

    /** Trang sửa thông tin quyền tài khoản */
    public function edit(int $id)
    {
        $user = NguoiDung::with(['hoSoKhachHang', 'hoSoNhanVien.chucVu', 'hoSoQuanLy'])->findOrFail($id);
        $this->ensureUserInCurrentStore($user);
        $this->ensureCanManageTargetUser($user);

        $from = $this->normalizeListOrigin(request()->query('from'));
        $positionsStaff = $this->positionsForRole('nhân viên');
        $positionsManager = $this->positionsForRole('quản lý');

        $roleOptions = [
            'khách hàng' => 'Khách hàng',
            'nhân viên' => 'Nhân viên',
        ];

        if ($this->actorCanManageAdmins()) {
            $roleOptions['quản lý'] = 'Quản lý';
        }

        $viewGroup = $this->resolveUserViewGroup($user->vai_tro);

        return view("manager.users.{$viewGroup}.edit", compact('user', 'roleOptions', 'from', 'positionsStaff', 'positionsManager'));
    }

    /** Cập nhật vai trò tài khoản */
    public function updateRole(UpdateUserRoleRequest $request, int $id)
    {
        $user = NguoiDung::findOrFail($id);
        $this->ensureUserInCurrentStore($user);
        $this->ensureCanManageTargetUser($user);
        $validated = $request->validated();

        $newRole = $this->normalizeRole($request->input('vai_tro'));
        $newStatus = $request->input('trang_thai');
        $from = $this->normalizeListOrigin($request->input('from'));

        if (!$newRole) {
            return back()->with('error', 'Vai trò không hợp lệ.');
        }

        if (!$this->canManageRole($newRole)) {
            return back()->with('error', 'Bạn không có quyền gán vai trò này.');
        }

        if ($user->vai_tro === 'chủ cửa hàng' && $newRole !== 'chủ cửa hàng') {
            $ownerCount = \App\Models\NguoiDung::where('vai_tro', 'chủ cửa hàng')->count();
            if ($ownerCount <= 1) {
                return back()->with('error', 'Phải có ít nhất 1 tài khoản chủ cửa hàng trong hệ thống. Không thể hạ quyền tài khoản này.');
            }
        }

        if ($newRole === 'chủ cửa hàng' && !$this->actorCanManageAdmins()) {
            return back()->with('error', 'Bạn không có quyền gán vai trò chủ cửa hàng.');
        }

        if ((int) Auth::id() === (int) $user->id && $newStatus !== 'hoạt động') {
            return back()->with('error', 'Không thể tự chuyển tài khoản đang đăng nhập sang trạng thái không hoạt động.');
        }

        $oldRole = $user->vai_tro;
        $oldStatus = $user->trang_thai;
        $roleChanged = $oldRole !== $newRole;
        $statusChanged = $oldStatus !== $newStatus;

        if ($user->vai_tro === 'chủ cửa hàng' && $newStatus !== 'hoạt động' && $statusChanged) {
            $activeOwnerCount = \App\Models\NguoiDung::where('vai_tro', 'chủ cửa hàng')->where('trang_thai', 'hoạt động')->count();
            if ($activeOwnerCount <= 1) {
                return back()->with('error', 'Phải có ít nhất 1 tài khoản chủ cửa hàng đang hoạt động trong hệ thống.');
            }
        }

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

            $this->attachUserToCurrentStore($user);

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
                ->with('success', "Đã cập nhật quyền và trạng thái tài khoản {$user->email}.");
        }

        if ($roleChanged) {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã đổi quyền {$user->email} từ {$oldRole} sang {$newRole}.");
        }

        if ($statusChanged) {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật trạng thái tài khoản {$user->email}.");
        }

        if ($newRole === 'nhân viên') {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật thông tin nhân viên {$user->email}.");
        }

        if ($newRole === 'quản lý') {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật thông tin quản lý {$user->email}.");
        }

        if ($newRole === 'chủ cửa hàng') {
            return redirect()->route($redirectRoute)
                ->with('success', "Đã cập nhật thông tin chủ cửa hàng {$user->email}.");
        }

        return redirect()->route($redirectRoute)
            ->with('info', 'Không có thay đổi quyền nào được áp dụng.');
    }

    /** Thêm tài khoản khách hàng / nhân viên */
    public function store(StoreUserRequest $request)
    {
        $request->merge([
            'email' => $this->normalizeNullable($request->input('email')),
            'ngay_vao_lam' => now()->toDateString(),
        ]);

        $validated = $request->validated();


        if (!$validated['email']) {
            return back()
                ->withErrors(['email' => 'Vui lòng nhập email.'])
                ->withInput();
        }

        $role = $this->normalizeRole($validated['vai_tro']);
        if (!$role) {
            return back()->withErrors(['vai_tro' => 'Vai trò tài khoản không hợp lệ.'])->withInput();
        }

        if (!$this->canManageRole($role)) {
            return back()->withErrors(['vai_tro' => 'Bạn không có quyền tạo tài khoản với vai trò này.'])->withInput();
        }

        $origin = $this->normalizeListOrigin($validated['from'] ?? null);
        $redirectRoute = match (true) {
            $origin === 'customers' || $role === 'khách hàng' => 'manager.users.customers',
            ($origin === 'admins' || in_array($role, ['quản lý', 'chủ cửa hàng'], true)) && $this->actorCanManageAdmins() => 'manager.users.admins',
            default => 'manager.users.staffs',
        };

        $createdUser = null;

        DB::transaction(function () use ($validated, $role, $request, &$createdUser): void {
            $createdUser = NguoiDung::create([
                'cua_hang_id' => $this->currentStoreId(),
                'email' => $validated['email'],
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

        if (!$createdUser) {
            return back()->with('error', 'Không thể tạo tài khoản mới, vui lòng thử lại.');
        }

        return redirect()->route($redirectRoute)
            ->with('success', "Đã thêm tài khoản {$createdUser->email} ({$role}).");
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
        return DonHang::query()
            ->where('nguoi_dung_id', $user->id)
            ->whereHas('chiTietDonHang', function ($q) {
                $this->applyPaidStatusConstraint($q, 'chi_tiet_don_hang.trang_thai_thanh_toan');
            });
    }

    private function calculateCustomerSpending(NguoiDung $user): float
    {
        $query = ChiTietDonHang::whereIn('don_hang_id', $user->donHang()->select('id'));
        $this->applyPaidStatusConstraint($query, 'chi_tiet_don_hang.trang_thai_thanh_toan');
        return (float) $query->sum('tong_tien');
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
        return UserRole::normalize($role)?->value;
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
                    'chuc_vu_id' => null,
                ]
            );

            return;
        }

        if ($role === 'quản lý') {
            HoSoQuanLy::firstOrCreate(
                ['nguoi_dung_id' => $user->id],
                [
                    'chuc_vu_id' => $this->defaultManagerPositionId(),
                ]
            );
        }
    }

    private function defaultManagerPositionId(): ?int
    {
        $query = ChucVu::query();

        if ($this->positionRoleColumnAvailable()) {
            $position = $query->firstOrCreate(
                ['ten_chuc_vu' => 'Quản lý'],
                [
                    'mo_ta_chuc_vu' => 'Chức vụ dành cho tài khoản quản lý.',
                    'vai_tro_ap_dung' => 'quản lý',
                ]
            );

            if ((string) $position->vai_tro_ap_dung !== 'quản lý') {
                $position->update(['vai_tro_ap_dung' => 'quản lý']);
            }

            return $position->id;
        }

        return $query->firstOrCreate(
            ['ten_chuc_vu' => 'Quản lý'],
            ['mo_ta_chuc_vu' => 'Chức vụ dành cho tài khoản quản lý.']
        )->id;
    }

    private function updateStaffProfile(Request $request, NguoiDung $user): void
    {
        $staffProfile = HoSoNhanVien::firstOrCreate(
            ['nguoi_dung_id' => $user->id],
            [
                'chuc_vu_id' => null,
            ]
        );

        $positionId = $request->filled('chuc_vu_id')
            ? (int) $request->input('chuc_vu_id')
            : ($staffProfile->chuc_vu_id ?: null);

        $positionId = $this->resolvePositionIdForRole($positionId, 'nhân viên');

        if ($request->filled('chuc_vu_id') && !$positionId) {
            throw ValidationException::withMessages([
                'chuc_vu_id' => 'Chức vụ đã chọn không thuộc vai trò nhân viên.',
            ]);
        }

        $staffProfile->update([
            'ho_ten' => $this->normalizeNullable($request->input('ho_ten')),
            'ngay_sinh' => $this->normalizeNullable($request->input('ngay_sinh')),
            'dia_chi_tam_chu' => $this->normalizeNullable($request->input('dia_chi_tam_chu')),
            'so_dien_thoai' => $this->normalizeNullable($request->input('so_dien_thoai')),
            'chuc_vu_id' => $positionId,
            'ngay_vao_lam' => $this->normalizeNullable($request->input('ngay_vao_lam')),
        ]);
    }

    private function updateAdminProfile(Request $request, NguoiDung $user): void
    {
        $adminProfile = HoSoQuanLy::firstOrCreate(
            ['nguoi_dung_id' => $user->id],
            [
                'chuc_vu_id' => $this->defaultManagerPositionId(),
            ]
        );

        $requestedPositionId = $request->filled('chuc_vu_id')
            ? (int) $request->input('chuc_vu_id')
            : ($adminProfile->chuc_vu_id ?: $this->defaultManagerPositionId());

        $positionId = $this->resolvePositionIdForRole($requestedPositionId, 'quản lý');

        if ($request->filled('chuc_vu_id') && !$positionId) {
            throw ValidationException::withMessages([
                'chuc_vu_id' => 'Chức vụ đã chọn không thuộc vai trò quản lý.',
            ]);
        }

        $positionId = $positionId ?: $this->defaultManagerPositionId();

        $adminProfile->update([
            'ho_ten' => $this->normalizeNullable($request->input('ho_ten')),
            'ngay_sinh' => $this->normalizeNullable($request->input('ngay_sinh')),
            'dia_chi_tam_chu' => $this->normalizeNullable($request->input('dia_chi_tam_chu')),
            'so_dien_thoai' => $this->normalizeNullable($request->input('so_dien_thoai')),
            'chuc_vu_id' => $positionId,
            'ngay_vao_lam' => $this->normalizeNullable($request->input('ngay_vao_lam')),
        ]);
    }

    private function positionsForRole(string $role)
    {
        $query = ChucVu::query()->orderBy('ten_chuc_vu');

        if ($this->positionRoleColumnAvailable()) {
            $query->where('vai_tro_ap_dung', $role);
        }

        return $query->get();
    }

    private function resolvePositionIdForRole(?int $positionId, string $role): ?int
    {
        if (!$positionId) {
            return null;
        }

        $query = ChucVu::query()->whereKey($positionId);

        if ($this->positionRoleColumnAvailable()) {
            $query->where('vai_tro_ap_dung', $role);
        }

        return $query->exists() ? $positionId : null;
    }

    private function positionRoleColumnAvailable(): bool
    {
        static $hasColumn;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasTable('chuc_vu') && Schema::hasColumn('chuc_vu', 'vai_tro_ap_dung');
        }

        return $hasColumn;
    }

    private function normalizeListOrigin(?string $origin): ?string
    {
        if (!$origin) {
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

        if ($origin === 'admins' && $this->actorCanManageAdmins()) {
            return 'manager.users.admins';
        }

        return match ($fallbackRole) {
            'nhân viên' => 'manager.users.staffs',
            'quản lý', 'chủ cửa hàng' => $this->actorCanManageAdmins() ? 'manager.users.admins' : 'manager.users.staffs',
            default => 'manager.users.customers',
        };
    }

    private function resolveUserViewGroup(string $role): string
    {
        return match ($role) {
            'nhân viên' => 'staffs',
            'quản lý', 'chủ cửa hàng' => 'admins',
            default => 'customers',
        };
    }

    private function actorCanManageAdmins(): bool
    {
        $actor = Auth::guard('nguoi_dung')->user() ?? Auth::user();
        return $actor && $actor->vai_tro === 'chủ cửa hàng';
    }

    private function canManageRole(string $role): bool
    {
        $allowedRoles = $this->actorCanManageAdmins()
            ? ['khách hàng', 'nhân viên', 'quản lý', 'chủ cửa hàng']
            : ['khách hàng', 'nhân viên'];

        return in_array($role, $allowedRoles, true);
    }

    private function canConfirmRole(string $targetRole): bool
    {
        $actorRole = Auth::guard('nguoi_dung')->user()?->vai_tro;

        return match ($actorRole) {
            'chủ cửa hàng' => in_array($targetRole, ['nhân viên', 'quản lý', 'chủ cửa hàng'], true),
            'quản lý' => $targetRole === 'nhân viên',
            default => false,
        };
    }

    private function confirmableRolesForActor(): array
    {
        $actorRole = Auth::guard('nguoi_dung')->user()?->vai_tro;

        return match ($actorRole) {
            'chủ cửa hàng' => ['nhân viên', 'quản lý', 'chủ cửa hàng'],
            'quản lý' => ['nhân viên'],
            default => [],
        };
    }

    private function buildPendingApprovalQuery(Request $request): Builder
    {
        $roles = $this->confirmableRolesForActor();
        if ($roles === []) {
            abort(403, 'Bạn không có quyền xem danh sách chờ xác nhận.');
        }

        $query = NguoiDung::query()
            ->where('trang_thai', 'ngưng hoạt động')
            ->whereIn('vai_tro', $roles);

        $this->applyStoreScope($query);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search) {
                $subQuery->where('email', 'like', "%{$search}%");
            });
        }

        $requestedRole = trim((string) $request->input('requested_role', ''));
        if ($requestedRole !== '' && in_array($requestedRole, $roles, true)) {
            $query->where('vai_tro', $requestedRole);
        }

        return $query;
    }

    private function currentStoreId(): ?int
    {
        $actor = Auth::guard('nguoi_dung')->user() ?? Auth::user();
        if (!$actor) {
            return null;
        }

        return $actor->cua_hang_id ?: $actor->hoSoQuanLy?->cua_hang_id;
    }

    private function applyStoreScope(Builder $query, string $column = 'cua_hang_id'): Builder
    {
        $storeId = $this->currentStoreId();
        if ($storeId) {
            $query->where(function ($q) use ($column, $storeId) {
                $q->where($column, $storeId)
                    ->orWhereNull($column);
            });
        }

        return $query;
    }

    private function ensureUserInCurrentStore(NguoiDung $user): void
    {
        if ($this->actorCanManageAdmins()) {
            return; // Chủ cửa hàng (full quyền) được phép xem ở bất kỳ đâu
        }

        $storeId = $this->currentStoreId();
        if (!$storeId) {
            return;
        }

        if ((int) ($user->cua_hang_id ?? 0) !== (int) $storeId) {
            abort(403, 'Bạn không có quyền thao tác tại trang này.');
        }
    }

    private function ensureCanManageTargetUser(NguoiDung $targetUser): void
    {
        if ($this->actorCanManageAdmins()) {
            return;
        }

        if (in_array($targetUser->vai_tro, ['quản lý', 'chủ cửa hàng'], true)) {
            abort(403, 'Bạn chỉ được quản lý tài khoản khách hàng và nhân viên.');
        }
    }

    private function attachUserToCurrentStore(NguoiDung $user): void
    {
        $storeId = $this->currentStoreId();
        if (!$storeId) {
            return;
        }

        if ((int) ($user->cua_hang_id ?? 0) !== (int) $storeId) {
            $user->update(['cua_hang_id' => $storeId]);
        }
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
