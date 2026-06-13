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
                    <option value="" disabled {{ !$selectedShiftId ? 'selected' : '' }}>-- Vui lòng chọn ca làm việc --</option>
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
        <div class="text-12 text-muted mt-6">Nhập khi bắt đầu ca</div>
    </div>
</div>

@php
    $existingClose = $summary['existing_close'] ?? null;
    $isStarted = $existingClose !== null;
    $isClosed = $isStarted && $existingClose->chot_luc !== null;
@endphp

@if(!$isStarted)
<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Bắt đầu ca</span>
    </div>
    <div class="card-body">
        @if(!$shift)
            <div class="alert alert-warning mb-10">
                Vui lòng chọn ca làm việc ở bên trên để thao tác.
            </div>
        @else
            <div class="alert alert-info mb-10">
                Ca làm việc này chưa được khai báo tiền đầu ca. Hãy nhập số tiền đầu ca để bắt đầu.
            </div>
        @endif
        <form method="POST" action="{{ route('manager.shift-close.start') }}">
            @csrf
            <input type="hidden" name="ca_lam_viec_id" value="{{ $selectedShiftId }}">
            <div class="form-group mb-10" style="max-width: 400px;">
                <label class="form-label">Số tiền đầu ca (Tiền mặt tại quầy) <span>*</span></label>
                <input type="text"
                       name="so_tien_dau_ca"
                       class="form-control format-money"
                       value="0"
                       {{ !$shift ? 'disabled' : '' }}
                       required>
            </div>
            <button type="submit" class="btn btn-primary" {{ !$shift ? 'disabled' : '' }}>Xác nhận bắt đầu ca</button>
        </form>
    </div>
</div>
@else
<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Chốt ca</span>
    </div>
    <div class="card-body">
        @if($isClosed)
            <div class="alert alert-success mb-10">
                Ca này đã được chốt vào lúc {{ $existingClose->chot_luc->format('H:i d/m/Y') }} bởi {{ $existingClose->nguoiChot?->ho_ten ?? $existingClose->nguoiChot?->email }}.
            </div>
        @endif

        <form method="POST" action="{{ route('manager.shift-close.store') }}">
            @csrf
            <input type="hidden" name="ca_lam_viec_id" value="{{ $selectedShiftId }}">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Số tiền đầu ca</label>
                    <input type="text"
                           class="form-control"
                           value="{{ number_format($existingClose->so_tien_dau_ca, 0, ',', '.') }} đ"
                           disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" name="ghi_chu" class="form-control" maxlength="500" value="{{ old('ghi_chu', $existingClose->ghi_chu ?? '') }}" {{ $isClosed ? 'disabled' : '' }}>
                </div>
            </div>

            <div class="text-12 text-muted" style="margin-bottom: 10px;">
                Tiền mặt tại quầy = Tiền đầu ca + Doanh thu tiền mặt - Chi tiền mặt<br>
                Tiền tài khoản = Doanh thu chuyển khoản - Chi chuyển khoản
            </div>

            @if(!$isClosed)
                @if($hasUnpaidTables ?? false)
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('unpaid-tables-modal').style.display='flex'; document.body.style.overflow='hidden';">Xác nhận chốt ca</button>
                @else
                    <button type="submit" class="btn btn-primary">Xác nhận chốt ca</button>
                @endif
            @endif
        </form>
    </div>
</div>
@endif

<!-- Unpaid Tables Modal -->
@if($hasUnpaidTables ?? false)
<div id="unpaid-tables-modal" class="force-password-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="force-password-modal__backdrop" onclick="this.parentElement.style.display='none'; document.body.style.overflow='';"></div>
    <div class="force-password-modal__panel" style="width: min(420px, 92vw);">
        <div class="force-password-modal__title" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            Cảnh báo chốt ca
        </div>
        <p class="force-password-modal__desc" style="margin-bottom: 25px; margin-top: 10px; padding: 0 10px;">
            Hiện tại vẫn còn bàn chưa thanh toán (đang phục vụ). Hãy thanh toán hoặc trả bàn trước khi chốt ca.
        </p>
        <div style="display: flex; justify-content: center;">
            <button type="button" class="force-password-modal__submit" onclick="document.getElementById('unpaid-tables-modal').style.display='none'; document.body.style.overflow='';" style="background: #c49a6c; color: #1a120c; width: 100%;">Đồng ý</button>
        </div>
    </div>
</div>
@endif

@endsection
