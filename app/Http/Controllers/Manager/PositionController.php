<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ChucVu;
use App\Models\HoSoNhanVien;
use App\Models\HoSoQuanLy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    private const STAFF_ROLE = 'nhân viên';
    private const MANAGER_ROLE = 'quản lý';

    private function isStoreOwnerActor(): bool
    {
        return Auth::guard('nguoi_dung')->user()?->vai_tro === 'chủ cửa hàng';
    }

    private function ensureCanMutatePositions(): void
    {
        abort_if(! $this->isStoreOwnerActor(), 403, 'Chỉ chủ cửa hàng mới được thêm, sửa hoặc xóa chức vụ.');
    }

    private function findAccessiblePosition(int $id): ChucVu
    {
        $query = ChucVu::query();

        if (! $this->isStoreOwnerActor()) {
            $query->where('vai_tro_ap_dung', self::STAFF_ROLE);
        }

        return $query->findOrFail($id);
    }

    public function index(Request $request)
    {
        $isStoreOwnerActor = $this->isStoreOwnerActor();
        $search = trim((string) $request->input('search', ''));

        $positions = ChucVu::query()
            ->withCount(['hoSoNhanVien as so_nhan_vien', 'hoSoQuanLy as so_quan_ly'])
            ->when(! $isStoreOwnerActor, function ($builder) {
                $builder->where('vai_tro_ap_dung', self::STAFF_ROLE);
            })
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($sub) use ($search) {
                    $sub->where('ten_chuc_vu', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("CASE WHEN vai_tro_ap_dung = ? THEN 0 ELSE 1 END", [self::MANAGER_ROLE])
            ->orderBy('ten_chuc_vu')
            ->paginate(12)
            ->withQueryString();

        return view('manager.positions.index', [
            'positions' => $positions,
            'search' => $search,
            'isStoreOwnerActor' => $isStoreOwnerActor,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanMutatePositions();

        return view('manager.positions.create', [
            'selectedRole' => old('vai_tro_ap_dung', self::STAFF_ROLE),
            'allowedRoles' => [
                self::STAFF_ROLE => 'Nhân viên',
                self::MANAGER_ROLE => 'Quản lý',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanMutatePositions();

        if ($request->has('luong_co_ban') && is_string($request->luong_co_ban)) {
            $request->merge(['luong_co_ban' => str_replace(',', '', $request->luong_co_ban)]);
        }
        if ($request->has('luong_theo_gio') && is_string($request->luong_theo_gio)) {
            $request->merge(['luong_theo_gio' => str_replace(',', '', $request->luong_theo_gio)]);
        }

        $validated = $request->validate([
            'ten_chuc_vu' => [
                'required', 'string', 'max:100', 
                Rule::unique('chuc_vu', 'ten_chuc_vu')->where(function ($query) use ($request) {
                    return $query->where('vai_tro_ap_dung', $request->vai_tro_ap_dung)
                                 ->where('loai_hinh_lam_viec', $request->loai_hinh_lam_viec);
                })
            ],
            'vai_tro_ap_dung' => ['required', Rule::in([self::STAFF_ROLE, self::MANAGER_ROLE])],
            'mo_ta_chuc_vu' => ['nullable', 'string', 'max:1000'],
            'luong_co_ban' => ['nullable', 'numeric', 'min:0'],
            'luong_theo_gio' => ['nullable', 'numeric', 'min:0'],
            'loai_hinh_lam_viec' => ['nullable', 'string', Rule::in(['bán thời gian', 'toàn thời gian'])],
        ]);

        ChucVu::query()->create([
            'ten_chuc_vu' => trim($validated['ten_chuc_vu']),
            'vai_tro_ap_dung' => $validated['vai_tro_ap_dung'],
            'mo_ta_chuc_vu' => isset($validated['mo_ta_chuc_vu']) ? trim((string) $validated['mo_ta_chuc_vu']) : null,
            'luong_co_ban' => $validated['luong_co_ban'] ?? null,
            'luong_theo_gio' => $validated['luong_theo_gio'] ?? null,
            'loai_hinh_lam_viec' => $validated['loai_hinh_lam_viec'] ?? null,
        ]);

        return redirect()
            ->route('manager.positions.index')
            ->with('success', 'Đã thêm chức vụ mới.');
    }

    public function show(int $id)
    {
        $position = $this->findAccessiblePosition($id);
        $isManagerPosition = (string) $position->vai_tro_ap_dung === self::MANAGER_ROLE;
        $profileRoleLabel = $isManagerPosition ? 'Quản lý' : 'Nhân viên';

        if ($isManagerPosition) {
            $profiles = HoSoQuanLy::query()
                ->with(['nguoiDung', 'chucVu'])
                ->where('chuc_vu_id', $position->id)
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString();

            $assignedCount = HoSoQuanLy::query()->where('chuc_vu_id', $position->id)->count();

            $unassignedProfiles = HoSoQuanLy::query()
                ->with('nguoiDung')
                ->where(function($q) use ($position) {
                    $q->where('chuc_vu_id', '!=', $position->id)
                      ->orWhereNull('chuc_vu_id');
                })
                ->get();
        } else {
            $profiles = HoSoNhanVien::query()
                ->with(['nguoiDung', 'chucVu'])
                ->where('chuc_vu_id', $position->id)
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString();

            $assignedCount = HoSoNhanVien::query()->where('chuc_vu_id', $position->id)->count();

            $unassignedProfiles = HoSoNhanVien::query()
                ->with('nguoiDung')
                ->where(function($q) use ($position) {
                    $q->where('chuc_vu_id', '!=', $position->id)
                      ->orWhereNull('chuc_vu_id');
                })
                ->get();
        }

        return view('manager.positions.show', [
            'position' => $position,
            'profiles' => $profiles,
            'assignedCount' => $assignedCount,
            'profileRoleLabel' => $profileRoleLabel,
            'isManagerPosition' => $isManagerPosition,
            'isStoreOwnerActor' => $this->isStoreOwnerActor(),
            'unassignedProfiles' => $unassignedProfiles,
        ]);
    }

    public function assignProfiles(Request $request, int $id)
    {
        $this->ensureCanMutatePositions();
        $position = $this->findAccessiblePosition($id);
        $profileIds = $request->input('profile_ids', []);

        if (empty($profileIds)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một nhân sự.');
        }

        $isManagerPosition = (string) $position->vai_tro_ap_dung === self::MANAGER_ROLE;

        if ($isManagerPosition) {
            HoSoQuanLy::whereIn('id', $profileIds)->update(['chuc_vu_id' => $position->id]);
        } else {
            HoSoNhanVien::whereIn('id', $profileIds)->update(['chuc_vu_id' => $position->id]);
        }

        return back()->with('success', 'Đã thêm nhân sự vào chức vụ thành công.');
    }

    public function removeProfile(Request $request, int $id, int $profileId)
    {
        $this->ensureCanMutatePositions();
        $position = $this->findAccessiblePosition($id);
        $isManagerPosition = (string) $position->vai_tro_ap_dung === self::MANAGER_ROLE;

        if ($isManagerPosition) {
            HoSoQuanLy::where('id', $profileId)->where('chuc_vu_id', $position->id)->update(['chuc_vu_id' => null]);
        } else {
            HoSoNhanVien::where('id', $profileId)->where('chuc_vu_id', $position->id)->update(['chuc_vu_id' => null]);
        }

        return back()->with('success', 'Đã xóa nhân sự khỏi chức vụ này.');
    }

    public function edit(Request $request, int $id)
    {
        $this->ensureCanMutatePositions();

        $position = ChucVu::query()->findOrFail($id);

        return view('manager.positions.edit', [
            'position' => $position,
            'selectedRole' => old('vai_tro_ap_dung', $position->vai_tro_ap_dung),
            'allowedRoles' => [
                self::STAFF_ROLE => 'Nhân viên',
                self::MANAGER_ROLE => 'Quản lý',
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->ensureCanMutatePositions();

        $position = ChucVu::query()->findOrFail($id);

        if ($request->has('luong_co_ban') && is_string($request->luong_co_ban)) {
            $request->merge(['luong_co_ban' => str_replace(',', '', $request->luong_co_ban)]);
        }
        if ($request->has('luong_theo_gio') && is_string($request->luong_theo_gio)) {
            $request->merge(['luong_theo_gio' => str_replace(',', '', $request->luong_theo_gio)]);
        }

        $validated = $request->validate([
            'ten_chuc_vu' => [
                'required', 'string', 'max:100', 
                Rule::unique('chuc_vu', 'ten_chuc_vu')->where(function ($query) use ($request) {
                    return $query->where('vai_tro_ap_dung', $request->vai_tro_ap_dung)
                                 ->where('loai_hinh_lam_viec', $request->loai_hinh_lam_viec);
                })->ignore($position->id)
            ],
            'vai_tro_ap_dung' => ['required', Rule::in([self::STAFF_ROLE, self::MANAGER_ROLE])],
            'mo_ta_chuc_vu' => ['nullable', 'string', 'max:1000'],
            'luong_co_ban' => ['nullable', 'numeric', 'min:0'],
            'luong_theo_gio' => ['nullable', 'numeric', 'min:0'],
            'loai_hinh_lam_viec' => ['nullable', 'string', Rule::in(['bán thời gian', 'toàn thời gian'])],
        ]);

        $newRole = $validated['vai_tro_ap_dung'];
        $currentRole = (string) $position->vai_tro_ap_dung;

        if ($currentRole !== $newRole) {
            $assignedStaffCount = HoSoNhanVien::query()->where('chuc_vu_id', $position->id)->count();
            $assignedManagerCount = HoSoQuanLy::query()->where('chuc_vu_id', $position->id)->count();

            if (($assignedStaffCount + $assignedManagerCount) > 0) {
                return back()
                    ->withInput()
                    ->with('error', 'Không thể đổi vai trò áp dụng vì chức vụ này đang được gán cho nhân sự.');
            }
        }

        $position->update([
            'ten_chuc_vu' => trim($validated['ten_chuc_vu']),
            'vai_tro_ap_dung' => $newRole,
            'mo_ta_chuc_vu' => isset($validated['mo_ta_chuc_vu']) ? trim((string) $validated['mo_ta_chuc_vu']) : null,
            'luong_co_ban' => $validated['luong_co_ban'] ?? null,
            'luong_theo_gio' => $validated['luong_theo_gio'] ?? null,
            'loai_hinh_lam_viec' => $validated['loai_hinh_lam_viec'] ?? null,
        ]);

        return redirect()
            ->route('manager.positions.index')
            ->with('success', 'Đã cập nhật chức vụ.');
    }

    public function destroy(Request $request, int $id)
    {
        $this->ensureCanMutatePositions();

        $position = ChucVu::query()->findOrFail($id);
        $assignedStaffCount = HoSoNhanVien::query()->where('chuc_vu_id', $position->id)->count();
        $assignedManagerCount = HoSoQuanLy::query()->where('chuc_vu_id', $position->id)->count();
        $assignedCount = $assignedStaffCount + $assignedManagerCount;

        if ($assignedCount > 0) {
            return redirect()
                ->route('manager.positions.show', ['id' => $position->id])
                ->with('error', 'Không thể xóa chức vụ vì đang có nhân sự được gán (nhân viên: ' . $assignedStaffCount . ', quản lý: ' . $assignedManagerCount . ').');
        }

        $position->delete();

        return redirect()
            ->route('manager.positions.index')
            ->with('success', 'Đã xóa chức vụ.');
    }
}
