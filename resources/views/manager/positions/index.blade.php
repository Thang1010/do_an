@extends('manager.layout.app')

@section('title', 'Quản lý chức vụ')
@section('breadcrumb', 'Nhân sự / <strong>Quản lý chức vụ</strong>')

@section('content')
@php
    $isStoreOwnerActor = $isStoreOwnerActor ?? false;
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý chức vụ</h1>
        <p class="page-subtitle">
            {{ $isStoreOwnerActor
                ? 'Quản lý danh sách chức vụ của nhân viên và quản lý trong hệ thống'
                : 'Danh sách chức vụ nhân viên trong hệ thống' }}
        </p>
    </div>
    @if($isStoreOwnerActor)
    <div class="page-actions">
        <a href="{{ route('manager.positions.create') }}" class="btn btn-primary">Thêm chức vụ</a>
    </div>
    @endif
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('manager.positions.index') }}" class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm theo tên chức vụ..." value="{{ $search }}">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Tên chức vụ</th>
                    <th>Loại hình</th>
                    <th>Vai trò áp dụng</th>
                    <th>Mô tả</th>
                    <th>Số nhân sự</th>
                    <th class="col-action-xl">{{ $isStoreOwnerActor ? 'Thao tác' : 'Xem' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($positions as $index => $position)
                @php
                    $isManagerRole = (string) ($position->vai_tro_ap_dung ?? 'nhân viên') === 'quản lý';
                    $assignedCount = $isManagerRole ? (int) ($position->so_quan_ly ?? 0) : (int) ($position->so_nhan_vien ?? 0);
                @endphp
                <tr>
                    <td>{{ $positions->firstItem() + $index }}</td>
                    <td>{{ $position->ten_chuc_vu }}</td>
                    <td>{{ ucfirst($position->loai_hinh_lam_viec ?? 'Toàn thời gian') }}</td>
                    <td>{{ $isManagerRole ? 'quản lý' : 'nhân viên' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($position->mo_ta_chuc_vu ?? '—', 60) }}</td>
                    <td>{{ number_format($assignedCount, 0, ',', '.') }}</td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.positions.show', ['id' => $position->id]) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                            @if($isStoreOwnerActor)
                            <a href="{{ route('manager.positions.edit', ['id' => $position->id]) }}" class="btn btn-secondary btn-sm">Sửa</a>
                            <form method="POST" action="{{ route('manager.positions.destroy', ['id' => $position->id]) }}"
                                  onsubmit="return confirmDelete(this, 'Xóa chức vụ {{ addslashes($position->ten_chuc_vu) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">Chưa có chức vụ nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($positions->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $positions->firstItem() }}-{{ $positions->lastItem() }} / {{ $positions->total() }} chức vụ
            </span>
            {{ $positions->links() }}
        </div>
    </div>
    @endif
</div>

@endsection
