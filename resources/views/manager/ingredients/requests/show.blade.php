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
<div style="display: flex; gap: 10px; margin-bottom: 20px;">
    <button type="button" class="btn btn-primary" onclick="openApproveModal({{ $ingredientRequest->id }})">Xác nhận yêu cầu</button>

    <button type="button" class="btn btn-danger" onclick="openRejectModal({{ $ingredientRequest->id }})">Từ chối yêu cầu</button>
</div>

<div class="modal-backdrop" id="reject-modal">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Từ chối yêu cầu</span>
            <button class="modal-close" onclick="closeModal('reject-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="reject-form" method="POST" action="">
                @csrf
                <div class="form-group">
                    <label class="form-label">Lý do từ chối <span>*</span></label>
                    <textarea name="review_note" class="form-control" rows="3" required placeholder="Nhập lý do..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('reject-modal')">Hủy</button>
            <button type="submit" form="reject-form" class="btn btn-danger">Xác nhận từ chối</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="approve-modal">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Xác nhận yêu cầu</span>
            <button class="modal-close" onclick="closeModal('approve-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <p>Bạn có chắc chắn muốn xác nhận yêu cầu thêm nguyên liệu này không?</p>
            <form id="approve-form" method="POST" action="">
                @csrf
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('approve-modal')">Hủy</button>
            <button type="submit" form="approve-form" class="btn btn-primary">Xác nhận</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openRejectModal(id) {
        document.getElementById('reject-form').action = '/manager/ingredient-requests/' + id + '/reject';
        openModal('reject-modal');
    }
    
    function openApproveModal(id) {
        document.getElementById('approve-form').action = '/manager/ingredient-requests/' + id + '/approve';
        openModal('approve-modal');
    }
</script>
@endpush
@endif
@endsection
