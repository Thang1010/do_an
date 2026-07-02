@extends('manager.layout.app')

@section('title', 'Sửa người dùng')
@section('breadcrumb', 'Nhân sự / Quản lý người dùng / <strong>Sửa</strong>')

@section('content')
@php
    $listRoute = match ($from ?? null) {
        'staff', 'staffs' => 'manager.users.staffs',
        'admins' => 'manager.users.admins',
        default => 'manager.users.customers',
    };
    $showStaffFields = old('vai_tro', $user->vai_tro) === 'nhân viên';
    $showAdminFields = old('vai_tro', $user->vai_tro) === 'quản lý';
    $positionsStaff = $positionsStaff ?? ($positions ?? collect());
    $positionsManager = $positionsManager ?? ($positions ?? collect());
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Sửa quyền người dùng</h1>
        <p class="page-subtitle">Cập nhật vai trò tài khoản và lưu thay đổi</p>
    </div>
    <div class="page-actions">
        <a href="{{ route($listRoute) }}" class="btn btn-secondary">Quay lại</a>
        <a href="{{ route('manager.users.show', $user->id) }}" class="btn btn-secondary">Xem chi tiết</a>
    </div>
</div>

<div class="card" style="max-width: 760px;">
    <div class="card-header">
        <div class="card-title">Thông tin tài khoản</div>
    </div>

    <div class="card-body">
        <div class="grid-2 mb-4">
            <div>
                <div class="text-muted text-sm">Họ tên</div>
                <div class="font-semibold">{{ $user->ho_ten }}</div>
            </div>
            <div>
                <div class="text-muted text-sm">Vai trò hiện tại</div>
                <div class="font-semibold">{{ ucfirst($user->vai_tro) }}</div>
            </div>
            <div>
                <div class="text-muted text-sm">Email</div>
                <div class="font-semibold">{{ $user->email ?? '—' }}</div>
            </div>
            <div>
                <div class="text-muted text-sm">Số điện thoại</div>
                <div class="font-semibold">{{ $user->so_dien_thoai ?? '—' }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('manager.users.role.update', $user->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="from" value="{{ $from }}">

            <div class="form-group">
                <label class="form-label" for="vai_tro">Vai trò mới <span>*</span></label>
                <select id="vai_tro" name="vai_tro" class="form-control" required>
                    @foreach($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('vai_tro', $user->vai_tro) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('vai_tro')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="trang_thai">Trạng thái tài khoản <span>*</span></label>
                <select id="trang_thai" name="trang_thai" class="form-control" required>
                    <option value="hoạt động" {{ old('trang_thai', $user->trang_thai) === 'hoạt động' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="ngưng hoạt động" {{ old('trang_thai', $user->trang_thai) === 'ngưng hoạt động' ? 'selected' : '' }}>Ngưng hoạt động</option>
                </select>
                @error('trang_thai')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div id="staff-fields" @if(! $showStaffFields) style="display:none;" @endif>
                <div class="divider"></div>

                <div class="form-group">
                    <label class="form-label" for="chuc_vu_id">Chức vụ</label>
                    <select id="chuc_vu_id" name="chuc_vu_id" class="form-control">
                        <option value="">-- Chọn chức vụ --</option>
                        @foreach($positionsStaff as $position)
                            <option value="{{ $position->id }}" {{ (string) old('chuc_vu_id', $user->hoSoNhanVien?->chuc_vu_id) === (string) $position->id ? 'selected' : '' }}>
                                {{ $position->ten_chuc_vu }} ({{ ucfirst($position->loai_hinh_lam_viec ?? 'toàn thời gian') }})
                            </option>
                        @endforeach
                    </select>
                    @error('chuc_vu_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mt-3">
                    <label class="form-label" for="ngay_vao_lam">Ngày vào làm</label>
                    <input
                        id="ngay_vao_lam"
                        type="date"
                        name="ngay_vao_lam"
                        class="form-control"
                        value="{{ old('ngay_vao_lam', optional($user->hoSoNhanVien?->ngay_vao_lam)->format('Y-m-d') ?? date('Y-m-d')) }}"
                    >
                    @error('ngay_vao_lam')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div id="admin-fields" @if(! $showAdminFields) style="display:none;" @endif>
                <div class="divider"></div>

                <div class="form-group">
                    <label class="form-label" for="admin_chuc_vu_id">Chức vụ quản lý</label>
                    <select id="admin_chuc_vu_id" name="chuc_vu_id" class="form-control">
                        <option value="">-- Chọn chức vụ --</option>
                        @foreach($positionsManager as $position)
                            <option value="{{ $position->id }}" {{ (string) old('chuc_vu_id', $user->hoSoQuanLy?->chuc_vu_id) === (string) $position->id ? 'selected' : '' }}>
                                {{ $position->ten_chuc_vu }} ({{ ucfirst($position->loai_hinh_lam_viec ?? 'toàn thời gian') }})
                            </option>
                        @endforeach
                    </select>
                    @error('chuc_vu_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin_ngay_vao_lam">Ngày vào làm</label>
                    <input
                        id="admin_ngay_vao_lam"
                        type="date"
                        name="ngay_vao_lam"
                        class="form-control"
                        value="{{ old('ngay_vao_lam', optional($user->hoSoQuanLy?->ngay_vao_lam)->format('Y-m-d') ?? date('Y-m-d')) }}"
                    >
                    @error('ngay_vao_lam')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="divider"></div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
                <a href="{{ route($listRoute) }}" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const roleSelect = document.getElementById('vai_tro');
        const staffFields = document.getElementById('staff-fields');

        if (!roleSelect || !staffFields) {
            return;
        }

        const adminFields = document.getElementById('admin-fields');

        const setGroupEnabled = (group, enabled) => {
            if (!group) {
                return;
            }

            group.querySelectorAll('input, select, textarea').forEach((el) => {
                el.disabled = !enabled;
            });
        };

        const toggleRoleFields = () => {
            const role = roleSelect.value;
            const staffEnabled = role === 'nhân viên';
            staffFields.style.display = staffEnabled ? 'block' : 'none';
            setGroupEnabled(staffFields, staffEnabled);

            if (adminFields) {
                const adminEnabled = role === 'quản lý';
                adminFields.style.display = adminEnabled ? 'block' : 'none';
                setGroupEnabled(adminFields, adminEnabled);
            }
        };

        roleSelect.addEventListener('change', toggleRoleFields);
        toggleRoleFields();
    })();
</script>
@endsection
