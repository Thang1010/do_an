@extends('manager.layout.app')

@section('title', 'Quản lý người dùng — Quản lý')
@section('breadcrumb', 'Nhân sự / <strong>Quản lý</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý tài khoản quản lý</h1>
        <p class="page-subtitle">{{ $totalAdmins ?? 0 }} tài khoản quản lý trong hệ thống</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('create-admin-modal')">Thêm tài khoản</button>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('manager.users.admins') }}" class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm tên / email / SĐT..." value="{{ request('search') }}">
        <select name="trang_thai" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="hoat_dong" {{ request('trang_thai') === 'hoat_dong' ? 'selected' : '' }}>Hoạt động</option>
            <option value="bi_khoa" {{ request('trang_thai') === 'bi_khoa' ? 'selected' : '' }}>Bị khóa</option>
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.users.admins') }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Họ tên</th>
                    <th>Email / SĐT</th>
                    <th>Vai trò</th>
                    <th>Loại hình</th>
                    <th>Trạng thái</th>
                    <th class="col-action-lg">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins ?? [] as $i => $user)
                @php
                    $actorRole = auth()->user()->vai_tro ?? null;
                    $canConfirmThisUser = $user->trang_thai === 'ngưng hoạt động'
                        && $actorRole === 'chủ cửa hàng';

                    $statusLabel = match ($user->trang_thai) {
                        'hoạt động' => 'Hoạt động',
                        'ngưng hoạt động' => 'Chờ xác nhận',
                        default => 'Bị khóa',
                    };
                @endphp
                <tr>
                    <td>{{ ($admins->firstItem() ?? 1) + $i }}</td>
                    <td><div class="font-600">{{ $user->ho_ten }}</div></td>
                    <td>
                        <div class="text-13">{{ $user->email ?? '—' }}</div>
                        <div class="text-12 text-muted">{{ $user->so_dien_thoai ?? '—' }}</div>
                    </td>
                    <td><span class="badge badge-outline">{{ ucfirst($user->vai_tro) }}</span></td>
                    <td><span class="badge badge-outline">{{ ucfirst($user->hoSoQuanLy?->loai_hinh_lam_viec ?? 'Toàn thời gian') }}</span></td>
                    <td>
                        <span class="badge {{ $user->trang_thai === 'hoạt động' ? 'badge-active' : 'badge-inactive' }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        <div class="action-row">
                            @if($canConfirmThisUser)
                            <form method="POST" action="{{ route('account-approvals.confirm', $user->id) }}" onsubmit="return confirm('Xác nhận kích hoạt tài khoản {{ $user->ho_ten }}?')">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Xác nhận</button>
                            </form>
                            @endif
                            <a href="{{ route('manager.users.show', $user->id) }}" class="btn btn-secondary btn-sm">Chi tiết</a>
                            @if($user->vai_tro !== 'chủ cửa hàng')
                                <a href="{{ route('manager.users.edit', ['id' => $user->id, 'from' => 'admins']) }}" class="btn btn-warning btn-sm">Sửa</a>
                                <form method="POST" action="{{ route('manager.users.destroy', $user->id) }}" onsubmit="return confirmDelete(this, 'Bạn có chắc muốn xóa tài khoản {{ addslashes($user->ho_ten) }}?')">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="from" value="admins">
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">Không tìm thấy tài khoản quản lý nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($admins) && method_exists($admins, 'hasPages') && $admins->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="text-sm text-muted">Hiển thị {{ $admins->firstItem() }}–{{ $admins->lastItem() }} / {{ $admins->total() }}</span>
            {{ $admins->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

<div class="modal-backdrop" id="create-admin-modal" data-auto-open="{{ ($errors->any() && old('from') === 'admins') ? '1' : '0' }}">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Thêm tài khoản quản lý</span>
            <button class="modal-close" onclick="closeModal('create-admin-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="create-admin-form" method="POST" action="{{ route('manager.users.store') }}">
                @csrf
                <input type="hidden" name="vai_tro" value="quản lý">
                <input type="hidden" name="from" value="admins">

                <div class="form-group">
                    <label class="form-label">Họ tên <span>*</span></label>
                    <input type="text" name="ho_ten" class="form-control" value="{{ old('ho_ten') }}" maxlength="150" required>
                    @error('ho_ten') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="150" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>




                <div class="form-group">
                    <label class="form-label">Loại hình làm việc</label>
                    <select name="loai_hinh_lam_viec" class="form-control">
                        <option value="toàn thời gian" {{ old('loai_hinh_lam_viec') === 'toàn thời gian' ? 'selected' : '' }}>Toàn thời gian</option>
                        <option value="bán thời gian" {{ old('loai_hinh_lam_viec') === 'bán thời gian' ? 'selected' : '' }}>Bán thời gian</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu <span>*</span></label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nhập lại mật khẩu <span>*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-admin-modal')">Hủy</button>
            <button type="submit" form="create-admin-form" class="btn btn-primary">Tạo tài khoản</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('create-admin-modal');
        if (modal && modal.dataset.autoOpen === '1') {
            openModal('create-admin-modal');
        }
    });
</script>
@endpush
