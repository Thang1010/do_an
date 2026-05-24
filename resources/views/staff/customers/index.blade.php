@extends('staff.layout.app')
@section('title', 'Khách hàng')
@section('breadcrumb', 'Quản lý / <strong>Khách hàng</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Khách hàng</h1>
        <p class="page-subtitle">Gửi yêu cầu cấp lại mật khẩu cho khách hàng</p>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('staff.customers.index') }}" class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Email hoặc số điện thoại" value="{{ $filters['search'] ?? '' }}">
        <input type="date" name="created_date" class="form-control" value="{{ $filters['created_date'] ?? '' }}">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('staff.customers.index') }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Khách hàng</span>
        <span class="text-12 text-muted">{{ $customers->total() }} tài khoản</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $i => $user)
                @php
                    $isPending = $user->trang_thai === 'ngưng hoạt động';
                @endphp
                <tr>
                    <td>{{ ($customers->firstItem() ?? 1) + $i }}</td>
                    <td class="font-600">{{ $user->ho_ten }}</td>
                    <td>{{ $user->email ?? '—' }}</td>
                    <td>{{ $user->so_dien_thoai ?? '—' }}</td>
                    <td class="text-muted text-12">{{ optional($user->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge {{ $isPending ? 'badge-pending' : 'badge-done' }}">
                            {{ $isPending ? 'Chưa xác minh email' : 'Hoạt động' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('staff.customers.password-reset', $user->id) }}"
                              onsubmit="return confirm('Gửi yêu cầu cấp lại mật khẩu cho {{ $user->ho_ten }}?')">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">Yêu cầu cấp lại mật khẩu</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">Chưa có khách hàng.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">Hiển thị {{ $customers->firstItem() }}-{{ $customers->lastItem() }} / {{ $customers->total() }}</span>
            {{ $customers->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
