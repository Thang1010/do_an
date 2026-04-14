@extends('layouts.manager')

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
                    <th>Mã quản lý</th>
                    <th>Ngày vào làm</th>
                    <th>Trạng thái</th>
                    <th class="col-action-lg">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins ?? [] as $i => $user)
                <tr>
                    <td>{{ ($admins->firstItem() ?? 1) + $i }}</td>
                    <td><div class="font-600">{{ $user->ho_ten }}</div></td>
                    <td>
                        <div class="text-13">{{ $user->email ?? '—' }}</div>
                        <div class="text-12 text-muted">{{ $user->so_dien_thoai ?? '—' }}</div>
                    </td>
                    <td>{{ $user->hoSoQuanLy->ma_quan_ly ?? '—' }}</td>
                    <td class="text-12 text-muted">{{ optional($user->hoSoQuanLy?->ngay_vao_lam)->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $user->trang_thai === 'hoạt động' ? 'badge-active' : 'badge-inactive' }}">
                            {{ $user->trang_thai === 'hoạt động' ? 'Hoạt động' : 'Bị khóa' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.users.show', $user->id) }}" class="btn btn-secondary btn-sm">Chi tiết</a>
                            <a href="{{ route('manager.users.edit', ['id' => $user->id, 'from' => 'admins']) }}" class="btn btn-warning btn-sm">Sửa</a>
                            <form method="POST" action="{{ route('manager.users.destroy', $user->id) }}" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản {{ $user->ho_ten }}?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="from" value="admins">
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
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
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="150" placeholder="Có thể bỏ trống nếu dùng SĐT để đăng nhập">
                </div>

                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai') }}" maxlength="20" placeholder="Có thể bỏ trống nếu dùng email để đăng nhập">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Ngày vào làm</label>
                        <input type="date" name="ngay_vao_lam" class="form-control" value="{{ old('ngay_vao_lam') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngân hàng</label>
                        <input type="text" name="ngan_hang" class="form-control" value="{{ old('ngan_hang') }}" maxlength="150">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Số tài khoản</label>
                    <input type="text" name="so_tai_khoan" class="form-control" value="{{ old('so_tai_khoan') }}" maxlength="50">
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu <span>*</span></label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nhập lại mật khẩu <span>*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-admin-modal')">Hủy</button>
            <button class="btn btn-primary" onclick="document.getElementById('create-admin-form').submit()">Tạo tài khoản</button>
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
