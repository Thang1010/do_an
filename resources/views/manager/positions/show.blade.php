@extends('manager.layout.app')

@php
    $profileRoleLabel = $profileRoleLabel ?? (((string) ($position->vai_tro_ap_dung ?? 'nhân viên') === 'quản lý') ? 'Quản lý' : 'Nhân viên');
    $isManagerPosition = $isManagerPosition ?? ((string) ($position->vai_tro_ap_dung ?? 'nhân viên') === 'quản lý');
@endphp

@section('title', 'Chi tiết chức vụ')
@section('breadcrumb')
Nhân sự / <a href="{{ route('manager.positions.index') }}">Quản lý chức vụ</a> / <strong>Chi tiết</strong>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $position->ten_chuc_vu }}</h1>
        <p class="page-subtitle">Thông tin chức vụ và danh sách {{ strtolower($profileRoleLabel) }} thuộc chức vụ này</p>
    </div>
    <div class="page-actions">
        @if($isStoreOwnerActor ?? false)
        <a href="{{ route('manager.positions.edit', ['id' => $position->id]) }}" class="btn btn-primary">Sửa chức vụ</a>
        @endif
        <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Tên chức vụ</div>
        <div class="stat-value" style="font-size: 24px;">{{ $position->ten_chuc_vu }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Số {{ strtolower($profileRoleLabel) }} đang gán</div>
        <div class="stat-value">{{ number_format($assignedCount, 0, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Vai trò áp dụng</div>
        <div class="stat-value" style="font-size: 20px;">{{ $position->vai_tro_ap_dung ?? 'nhân viên' }}</div>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Mô tả chức vụ</span>
    </div>
    <div class="card-body">
        {{ $position->mo_ta_chuc_vu ?: 'Chưa có mô tả.' }}
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">{{ $profileRoleLabel }} thuộc chức vụ này</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>{{ $isManagerPosition ? 'Mã quản lý' : 'Mã nhân viên' }}</th>
                    <th>Họ tên</th>
                    <th>Vai trò tài khoản</th>
                    <th>Trạng thái</th>
                    <th>Chức vụ</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profiles as $index => $profile)
                <tr>
                    <td>{{ $profiles->firstItem() + $index }}</td>
                    <td>{{ $isManagerPosition ? ($profile->ma_quan_ly ?? '—') : ($profile->ma_nhan_vien ?? '—') }}</td>
                    <td>{{ $profile->nguoiDung?->ho_ten ?? '—' }}</td>
                    <td>{{ $profile->nguoiDung?->vai_tro ?? '—' }}</td>
                    <td>{{ $profile->nguoiDung?->trang_thai ?? '—' }}</td>
                    <td>{{ $profile->chucVu?->ten_chuc_vu ?? '—' }}</td>
                    <td>
                        <a href="{{ route('manager.users.show', $profile->nguoi_dung_id) }}" class="btn btn-secondary btn-sm">Xem nhân viên</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">Chưa có {{ strtolower($profileRoleLabel) }} nào thuộc chức vụ này.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($profiles->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $profiles->firstItem() }}-{{ $profiles->lastItem() }} / {{ $profiles->total() }} {{ strtolower($profileRoleLabel) }}
            </span>
            {{ $profiles->links() }}
        </div>
    </div>
    @endif
</div>

@endsection
