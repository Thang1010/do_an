@extends('manager.layout.app')

@section('title', 'Quản lý người dùng — Khách hàng')
@section('breadcrumb', 'Nhân sự / <strong>Khách hàng</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý tài khoản khách hàng</h1>
        <p class="page-subtitle">{{ $totalCustomers ?? 0 }} khách hàng đã đăng ký</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('create-customer-modal')">Thêm tài khoản</button>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('manager.users.customers') }}"
          class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm tên / email / SĐT..." value="{{ request('search') }}">
        <select name="trang_thai" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="hoat_dong" {{ request('trang_thai')==='hoat_dong' ? 'selected' : '' }}>Hoạt động</option>
            <option value="ngung_hoat_dong" {{ request('trang_thai')==='ngung_hoat_dong' ? 'selected' : '' }}>Ngưng hoạt động</option>
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.users.customers') }}" class="btn btn-secondary">Xóa lọc</a>
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
                    <th>Trạng thái</th>
                    <th class="col-action-lg">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers ?? [] as $i => $user)
                @php
                    $statusLabel = match ($user->trang_thai) {
                        'hoạt động' => 'Hoạt động',
                        'ngưng hoạt động' => 'Ngưng hoạt động',
                        default => 'Ngưng hoạt động',
                    };
                @endphp
                <tr>
                    <td>{{ ($customers->firstItem() ?? 1) + $i }}</td>
                    <td>
                        <div class="font-600">{{ $user->ho_ten }}</div>
                    </td>
                    <td>
                        <div class="text-13">{{ $user->email ?? '—' }}</div>
                        <div class="text-12 text-muted">{{ $user->so_dien_thoai ?? '—' }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $user->trang_thai === 'hoạt động' ? 'badge-active' : 'badge-inactive' }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.users.show', $user->id) }}" class="btn btn-secondary btn-sm">Chi tiết</a>
                            <a href="{{ route('manager.users.edit', ['id' => $user->id, 'from' => 'customers']) }}" class="btn btn-warning btn-sm">Sửa</a>
                            <form method="POST" action="{{ route('manager.users.destroy', $user->id) }}" onsubmit="return confirmDelete(this, 'Bạn có chắc muốn xóa tài khoản {{ addslashes($user->ho_ten) }}?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="from" value="customers">
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        Không tìm thấy khách hàng nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($customers) && method_exists($customers,'hasPages') && $customers->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="text-sm text-muted">Hiển thị {{ $customers->firstItem() }}–{{ $customers->lastItem() }} / {{ $customers->total() }}</span>
            {{ $customers->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

<div class="modal-backdrop" id="create-customer-modal" data-auto-open="{{ ($errors->any() && old('from') === 'customers') ? '1' : '0' }}">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Thêm tài khoản</span>
            <button class="modal-close" onclick="closeModal('create-customer-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="create-customer-form" method="POST" action="{{ route('manager.users.store') }}">
                @csrf
                <input type="hidden" name="vai_tro" value="khách hàng">
                <input type="hidden" name="from" value="customers">

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
            <button class="btn btn-secondary" onclick="closeModal('create-customer-modal')">Hủy</button>
            <button type="submit" form="create-customer-form" class="btn btn-primary">Tạo tài khoản</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('create-customer-modal');
        if (modal && modal.dataset.autoOpen === '1') {
            openModal('create-customer-modal');
        }
    });
</script>
@endpush
