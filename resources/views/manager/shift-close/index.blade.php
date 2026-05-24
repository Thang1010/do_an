@extends('manager.layout.app')

@section('title', 'Chốt ca')
@section('breadcrumb', 'Kinh doanh / <strong>Chốt ca</strong>')

@section('content')
@php
    $shift = $summary['shift'] ?? null;
    $shiftLabel = $shift
        ? sprintf('%s • %s (%s - %s)', $shift->ngay_lam, $shift->ten_ca, $shift->gio_bat_dau, $shift->gio_ket_thuc)
        : 'Chưa có ca làm việc';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Chốt ca</h1>
        <p class="page-subtitle">Tổng hợp doanh thu và chi tiêu theo ca • {{ $shiftLabel }}</p>
    </div>
</div>

<div class="card mb-20">
    <div class="card-body">
        @if($shiftGroups->isEmpty())
            <div class="alert alert-warning">Chưa có ca làm việc để chốt.</div>
        @else
            <form method="GET" action="{{ route('manager.shift-close.index') }}" class="filter-bar mb-0">
                <select name="ca_lam_viec_id" class="form-control" onchange="this.form.submit()">
                    @foreach($shiftGroups as $group)
                        <option value="{{ $group->id }}" {{ (string) $selectedShiftId === (string) $group->id ? 'selected' : '' }}>
                            {{ $group->ngay_lam }} • {{ $group->ten_ca }} ({{ $group->gio_bat_dau }} - {{ $group->gio_ket_thuc }})
                        </option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-secondary">Xem ca</button></noscript>
            </form>
        @endif
    </div>
</div>

@if($shift)
<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Doanh thu tiền mặt</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_tien_mat'] ?? 0, 0, ',', '.') }}đ</div>
        <div class="text-12 text-muted mt-6">Tính theo thanh toán đã trả</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Doanh thu chuyển khoản</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_tien_chuyen_khoan'] ?? 0, 0, ',', '.') }}đ</div>
        <div class="text-12 text-muted mt-6">Tính theo thanh toán đã trả</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Chi tiêu trong ca</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format(($summary['tong_chi_mat'] ?? 0) + ($summary['tong_chi_chuyen_khoan'] ?? 0), 0, ',', '.') }}đ</div>
        <div class="text-12 text-muted mt-6">Tiền mặt + chuyển khoản</div>
    </div>
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Tiền mặt tại quầy</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['so_tien_quay'] ?? 0, 0, ',', '.') }}đ</div>
        <div class="text-12 text-muted mt-6">Số dư cuối ca</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tiền trong tài khoản</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['so_tien_tai_khoan'] ?? 0, 0, ',', '.') }}đ</div>
        <div class="text-12 text-muted mt-6">Số dư cuối ca</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tiền mặt đầu ca</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['so_tien_dau_ca'] ?? 0, 0, ',', '.') }}đ</div>
        <div class="text-12 text-muted mt-6">Nhập khi chốt ca</div>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Chốt ca</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('manager.shift-close.store') }}">
            @csrf
            <input type="hidden" name="ca_lam_viec_id" value="{{ $selectedShiftId }}">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Số tiền đầu ca <span>*</span></label>
                    <input type="number"
                           name="so_tien_dau_ca"
                           class="form-control"
                           min="0"
                           step="0.01"
                           value="{{ old('so_tien_dau_ca', $summary['so_tien_dau_ca'] ?? 0) }}"
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" name="ghi_chu" class="form-control" maxlength="500" value="{{ old('ghi_chu', $summary['existing_close']->ghi_chu ?? '') }}">
                </div>
            </div>

            <div class="text-12 text-muted" style="margin-bottom: 10px;">
                Tiền mặt tại quầy = Tiền đầu ca + Doanh thu tiền mặt - Chi tiền mặt<br>
                Tiền tài khoản = Doanh thu chuyển khoản - Chi chuyển khoản
            </div>

            <button type="submit" class="btn btn-primary">Lưu chốt ca</button>
        </form>
    </div>
</div>

@endif
@endsection
