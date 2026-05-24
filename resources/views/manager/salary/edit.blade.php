@extends('manager.layout.app')

@section('title', 'Sửa lương — ' . $user->ho_ten)
@section('breadcrumb', 'Kho & Tài chính / Chi tiêu / Lương / <strong>Sửa lương</strong>')

@section('content')
@php
    $isNhanVien = $user->vai_tro === 'nhân viên';
    $profile = $isNhanVien ? $user->hoSoNhanVien : $user->hoSoQuanLy;
    $loaiHinh = $profile->loai_hinh_lam_viec ?? 'toàn thời gian';
    $isBanThoiGian = $loaiHinh === 'bán thời gian';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa thiết lập lương — {{ $user->ho_ten }}</h1>
        <p class="page-subtitle">{{ ucfirst($user->vai_tro) }} • {{ $loaiHinh }}</p>
    </div>
    <a href="{{ route('manager.salary.index', ['thang' => $thang, 'nam' => $nam]) }}" class="btn btn-secondary">← Quay lại</a>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Thông tin tổng quan</span>
    </div>
    <div class="card-body">
        <div class="grid-3 mb-15">
            <div>
                <div class="text-12 text-muted mb-4">Họ tên</div>
                <div class="font-600">{{ $salaryRow['ho_ten'] }}</div>
            </div>
            <div>
                <div class="text-12 text-muted mb-4">Vai trò</div>
                <div class="font-600">{{ ucfirst($salaryRow['vai_tro']) }}</div>
            </div>
            <div>
                <div class="text-12 text-muted mb-4">Chức vụ</div>
                <div class="font-600">{{ $salaryRow['chuc_vu'] }}</div>
            </div>
        </div>
        <div class="grid-3">
            <div>
                <div class="text-12 text-muted mb-4">Loại hình làm việc</div>
                <div class="font-600">{{ ucfirst($salaryRow['loai_hinh']) }}</div>
            </div>
            <div>
                <div class="text-12 text-muted mb-4">Tổng giờ làm tháng này</div>
                <div class="font-600">{{ number_format($salaryRow['tong_gio'], 1) }} giờ</div>
            </div>
            <div>
                <div class="text-12 text-muted mb-4">Tạm tính lương tháng này</div>
                <div class="font-600 price-text">{{ number_format($salaryRow['tong_luong'], 0, ',', '.') }}đ</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Thiết lập mức lương</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('manager.salary.update', $user->id) }}">
            @csrf
            @method('PUT')

            @if($isBanThoiGian)
                <div class="form-group">
                    <label class="form-label">Lương theo giờ (đ/giờ) <span>*</span></label>
                    <input type="number"
                           name="luong_theo_gio"
                           class="form-control"
                           min="0"
                           step="0.01"
                           required
                           value="{{ old('luong_theo_gio', $profile->luong_theo_gio ?? 0) }}"
                           style="max-width: 400px;">
                    <div class="text-12 text-muted" style="margin-top: 6px;">
                        Công thức: Lương theo giờ × Tổng số giờ đã làm trong tháng
                    </div>
                </div>
            @else
                <div class="form-group">
                    <label class="form-label">Lương cơ bản (đ/tháng) <span>*</span></label>
                    <input type="number"
                           name="luong_co_ban"
                           class="form-control"
                           min="0"
                           step="0.01"
                           required
                           value="{{ old('luong_co_ban', $profile->luong_co_ban ?? 0) }}"
                           style="max-width: 400px;">
                    <div class="text-12 text-muted" style="margin-top: 6px;">
                        @if($isNhanVien)
                            Công thức: Lương cơ bản + Tổng doanh thu tháng × 0.5%
                        @else
                            Công thức: Lương cơ bản + Tổng doanh thu tháng × 1%
                        @endif
                    </div>
                </div>
            @endif

            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </form>
    </div>
</div>
@endsection
