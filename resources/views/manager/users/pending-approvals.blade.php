@extends('manager.layout.app')

@section('title', 'Yêu cầu đăng ký chờ xác nhận')
@section('breadcrumb', 'Nhân sự / <strong>Yêu cầu đăng ký chờ xác nhận</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Yêu cầu đăng ký chờ xác nhận</h1>
        <p class="page-subtitle">Xem tài khoản nhân viên/quản lý đang chờ duyệt</p>
    </div>
    <div class="page-actions">
        <form method="POST" action="{{ route('manager.users.pending-approvals.confirm') }}" onsubmit="return confirmSubmit(this, 'Xác nhận tất cả tài khoản theo bộ lọc hiện tại?')">
            @csrf
            <input type="hidden" name="confirm_scope" value="all">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="requested_role" value="{{ $requestedRole }}">
            <button type="submit" class="btn btn-primary">Xác nhận tất cả</button>
        </form>
    </div>
</div>

<div class="card mb-20">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.users.pending-approvals') }}" class="filter-bar mb-0">
            <input type="text" name="search" value="{{ $search }}" class="form-control filter-search" placeholder="Tìm theo tên, email, số điện thoại...">
            <select name="requested_role" class="form-control">
                <option value="">Tất cả vai trò</option>
                <option value="nhân viên" {{ $requestedRole === 'nhân viên' ? 'selected' : '' }}>Nhân viên</option>
                <option value="quản lý" {{ $requestedRole === 'quản lý' ? 'selected' : '' }}>Quản lý</option>
                <option value="chủ cửa hàng" {{ $requestedRole === 'chủ cửa hàng' ? 'selected' : '' }}>Chủ cửa hàng</option>
            </select>
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <a href="{{ route('manager.users.pending-approvals') }}" class="btn btn-secondary">Đặt lại</a>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('manager.users.pending-approvals.confirm') }}" id="pending-approvals-form">
    @csrf
    <input type="hidden" name="confirm_scope" value="selected">
    <input type="hidden" name="search" value="{{ $search }}">
    <input type="hidden" name="requested_role" value="{{ $requestedRole }}">

    <div class="card">
        <div class="card-header">
            <span class="card-title">Danh sách chờ xác nhận ({{ number_format((int) $pendingCount, 0, ',', '.') }})</span>
            <div class="action-row">
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllPending(true)">Chọn tất cả</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllPending(false)">Bỏ chọn</button>
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirmSelectedPending(this.form)">Xác nhận đã chọn</button>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-stt">Chọn</th>
                        <th>Họ tên</th>
                        <th>Email / SĐT</th>
                        <th>Vai trò đăng ký</th>
                        <th>Ngày gửi</th>
                        <th class="col-action-lg">Thao tác nhanh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingUsers as $user)
                    <tr>
                        <td>
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="pending-user-checkbox">
                        </td>
                        <td><span class="font-600">{{ $user->ho_ten }}</span></td>
                        <td>
                            <div>{{ $user->email ?? '—' }}</div>
                            <div class="text-12 text-muted">{{ $user->so_dien_thoai ?? '—' }}</div>
                        </td>
                        <td>{{ $user->vai_tro }}</td>
                        <td class="text-12 text-muted">{{ optional($user->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('account-approvals.confirm', $user->id) }}" onsubmit="return confirmSubmit(this, 'Xác nhận tài khoản {{ addslashes($user->ho_ten) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Xác nhận</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-state">Không có tài khoản nào đang chờ xác nhận theo bộ lọc hiện tại.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingUsers->hasPages())
        <div class="card-footer">
            <div class="pagination-footer">
                <span class="pagination-info">Hiển thị {{ $pendingUsers->firstItem() }}-{{ $pendingUsers->lastItem() }} / {{ $pendingUsers->total() }} tài khoản</span>
                {{ $pendingUsers->links() }}
            </div>
        </div>
        @endif
    </div>
</form>
@endsection

@push('scripts')
<script>
    function toggleAllPending(checked) {
        document.querySelectorAll('.pending-user-checkbox').forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }

    function confirmSelectedPending(form) {
        const selectedCount = document.querySelectorAll('.pending-user-checkbox:checked').length;

        if (selectedCount === 0) {
            showNotice('Vui lòng chọn ít nhất một tài khoản để xác nhận.');
            return false;
        }

        return confirmSubmit(form, 'Xác nhận ' + selectedCount + ' tài khoản đã chọn?');
    }
</script>
@endpush
