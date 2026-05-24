@extends('manager.layout.app')

@section('title', 'Chi tiết yêu cầu nguyên liệu')
@section('breadcrumb', 'Kho & Tài chính / <a href="' . route('manager.ingredients.requests.index') . '">Yêu cầu nguyên liệu</a> / <strong>Chi tiết yêu cầu</strong>')

@section('content')
@php
    $statusLabel = match ($ingredientRequest->trang_thai) {
        'da_duyet' => 'Đã duyệt',
        'tu_choi' => 'Từ chối',
        default => 'Chờ xác nhận',
    };
    $statusClass = match ($ingredientRequest->trang_thai) {
        'da_duyet' => 'badge-active',
        'tu_choi' => 'badge-inactive',
        default => 'badge-pending',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Chi tiết yêu cầu nguyên liệu #{{ $ingredientRequest->id }}</h1>
        <p class="page-subtitle">Xem danh sách nguyên liệu đã gửi và trạng thái xử lý</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.ingredients.requests.index') }}" class="btn btn-secondary">Quay lại danh sách yêu cầu</a>
    </div>
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Người gửi</div>
        <div class="stat-value" style="font-size: 20px;">{{ $ingredientRequest->nguoiGui->ho_ten ?? '—' }}</div>
        <div class="text-12 text-muted mt-6">{{ $ingredientRequest->nguoiGui->email ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Trạng thái</div>
        <div class="mt-8"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></div>
        <div class="text-12 text-muted mt-6">{{ optional($ingredientRequest->created_at)->format('d/m/Y H:i') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Người duyệt</div>
        <div class="stat-value" style="font-size: 20px;">{{ $ingredientRequest->nguoiDuyet->ho_ten ?? '—' }}</div>
        <div class="text-12 text-muted mt-6">
            @if($ingredientRequest->duyet_luc)
                Duyệt lúc: {{ optional($ingredientRequest->duyet_luc)->format('d/m/Y H:i') }}
            @elseif($ingredientRequest->tu_choi_luc)
                Từ chối lúc: {{ optional($ingredientRequest->tu_choi_luc)->format('d/m/Y H:i') }}
            @else
                Chưa xử lý
            @endif
        </div>
    </div>
</div>

@if($ingredientRequest->ghi_chu)
<div class="alert alert-info mb-20">
    <strong>Ghi chú duyệt:</strong> {{ $ingredientRequest->ghi_chu }}
</div>
@endif

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Danh sách nguyên liệu gửi duyệt</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Tên nguyên liệu</th>
                    <th>Đơn vị tính</th>
                    <th>Mục đích sử dụng</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><span class="font-600">{{ $item['ten_nguyen_lieu'] ?? '—' }}</span></td>
                    <td>{{ $item['don_vi_tinh'] ?? '—' }}</td>
                    <td>{{ $item['muc_dich_su_dung'] ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-state">Không có dữ liệu nguyên liệu trong yêu cầu này.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($isStoreOwner && $ingredientRequest->trang_thai === 'cho_xac_nhan')
<div class="card">
    <div class="card-header">
        <span class="card-title">Thao tác xác nhận</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('manager.ingredients.requests.approve', ['id' => $ingredientRequest->id]) }}" class="mb-16" onsubmit="return confirm('Duyệt yêu cầu này và thêm nguyên liệu vào danh mục?')">
            @csrf
            <div class="form-group">
                <label class="form-label">Ghi chú duyệt (tùy chọn)</label>
                <textarea name="review_note" class="form-control" rows="3" maxlength="1000" placeholder="Ví dụ: Đã kiểm tra danh mục, đồng ý thêm toàn bộ."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Duyệt yêu cầu</button>
        </form>

        <form method="POST" action="{{ route('manager.ingredients.requests.reject', ['id' => $ingredientRequest->id]) }}" onsubmit="return confirm('Bạn chắc chắn muốn từ chối yêu cầu này?')">
            @csrf
            <div class="form-group">
                <label class="form-label">Lý do từ chối (tùy chọn)</label>
                <textarea name="review_note" class="form-control" rows="3" maxlength="1000" placeholder="Ví dụ: Trùng nguyên liệu đã có, cần chỉnh lại đơn vị tính."></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Từ chối yêu cầu</button>
        </form>
    </div>
</div>
@endif
@endsection
