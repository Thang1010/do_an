@extends('manager.layout.app')

@section('title', $isStoreOwner ? 'Yêu cầu nguyên liệu chờ duyệt' : 'Yêu cầu nguyên liệu của tôi')
@section('breadcrumb', 'Kho & Tài chính / <a href="' . route('manager.ingredients.index') . '">Quản lý nguyên liệu</a> / <strong>Yêu cầu</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $isStoreOwner ? 'Yêu cầu nguyên liệu chờ duyệt' : 'Yêu cầu nguyên liệu của tôi' }}</h1>
        <p class="page-subtitle">Theo dõi trạng thái yêu cầu thêm nguyên liệu</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.ingredients.create') }}" class="btn btn-primary">{{ $isStoreOwner ? 'Thêm trực tiếp' : 'Tạo yêu cầu mới' }}</a>
        <a href="{{ route('manager.ingredients.index') }}" class="btn btn-secondary">Về danh mục nguyên liệu</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.ingredients.requests.index') }}" class="filter-bar mb-0">
            <input type="text" name="search" value="{{ $search }}" class="form-control filter-search" placeholder="Tìm theo người gửi hoặc ghi chú...">
            <select name="status" class="form-control">
                <option value="">Tất cả trạng thái</option>
                <option value="cho_xac_nhan" {{ $status === 'cho_xac_nhan' ? 'selected' : '' }}>Chờ xác nhận</option>
                <option value="da_duyet" {{ $status === 'da_duyet' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="tu_choi" {{ $status === 'tu_choi' ? 'selected' : '' }}>Từ chối</option>
            </select>
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <a href="{{ route('manager.ingredients.requests.index') }}" class="btn btn-secondary">Đặt lại</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Người gửi</th>
                    <th>Số nguyên liệu</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th>Người duyệt</th>
                    <th class="col-action">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $index => $requestItem)
                @php
                    $statusLabel = match ($requestItem->trang_thai) {
                        'da_duyet' => 'Đã duyệt',
                        'tu_choi' => 'Từ chối',
                        default => 'Chờ xác nhận',
                    };
                    $statusClass = match ($requestItem->trang_thai) {
                        'da_duyet' => 'badge-active',
                        'tu_choi' => 'badge-inactive',
                        default => 'badge-pending',
                    };
                @endphp
                <tr>
                    <td>{{ ($requests->firstItem() ?? 1) + $index }}</td>
                    <td>
                        <div class="font-600">{{ $requestItem->nguoiGui->ho_ten ?? '—' }}</div>
                        <div class="text-12 text-muted">{{ $requestItem->nguoiGui->email ?? '—' }}</div>
                    </td>
                    <td>{{ number_format(count((array) ($requestItem->du_lieu ?? [])), 0, ',', '.') }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td class="text-12 text-muted">{{ optional($requestItem->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $requestItem->nguoiDuyet->ho_ten ?? '—' }}</td>
                    <td>
                        <a href="{{ route('manager.ingredients.requests.show', ['id' => $requestItem->id]) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">Chưa có yêu cầu nguyên liệu nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">Hiển thị {{ $requests->firstItem() }}-{{ $requests->lastItem() }} / {{ $requests->total() }} yêu cầu</span>
            {{ $requests->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
