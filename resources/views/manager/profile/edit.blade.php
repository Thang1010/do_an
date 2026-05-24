@extends('manager.layout.app')

@section('title', 'Hồ sơ cá nhân')
@section('breadcrumb', 'Tổng quan / <strong>Hồ sơ cá nhân</strong>')

@section('content')
@php
    $managerProfile = $user->hoSoQuanLy;
    $storeProfile = $user->cuaHang;
    $staffProfile = $user->hoSoNhanVien;
    $customerProfile = $user->hoSoKhachHang;
    $isStoreOwner = $user->vai_tro === 'chủ cửa hàng';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Hồ sơ cá nhân</h1>
        <p class="page-subtitle">Cập nhật thông tin tài khoản và thông tin theo vai trò hiện tại</p>
    </div>
</div>

<form method="POST" action="{{ route('manager.profile.update') }}">
    @csrf
    @method('PUT')

    <div class="card mb-20">
        <div class="card-header">
            <span class="card-title">Thông tin tài khoản</span>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Họ tên <span>*</span></label>
                    <input type="text" name="ho_ten" class="form-control" value="{{ old('ho_ten', $user->ho_ten) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Vai trò</label>
                    <input type="text" class="form-control" value="{{ ucfirst($user->vai_tro) }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai', $user->so_dien_thoai) }}">
                </div>
            </div>
        </div>
    </div>

    @if($user->vai_tro === 'quản lý' || $isStoreOwner)
    <div class="card mb-20">
        <div class="card-header">
            <span class="card-title">{{ $isStoreOwner ? 'Thông tin chủ cửa hàng' : 'Thông tin quản lý' }}</span>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                @if($user->vai_tro === 'quản lý')
                <div class="form-group">
                    <label class="form-label">Chức vụ quản lý</label>
                    <select name="chuc_vu_id" class="form-control">
                        <option value="">-- Chọn chức vụ --</option>
                        @foreach($positions ?? [] as $position)
                            <option value="{{ $position->id }}" {{ (string) old('chuc_vu_id', $managerProfile?->chuc_vu_id) === (string) $position->id ? 'selected' : '' }}>
                                {{ $position->ten_chuc_vu }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Ngày vào làm</label>
                    <input type="date" name="ngay_vao_lam" class="form-control"
                           value="{{ old('ngay_vao_lam', optional($managerProfile?->ngay_vao_lam)->format('Y-m-d')) }}">
                </div>
                @endif

                @if($isStoreOwner)
                <div class="form-group">
                    <label class="form-label">Số tài khoản</label>
                    <input type="text" name="so_tai_khoan" class="form-control" value="{{ old('so_tai_khoan', $storeProfile?->so_tai_khoan) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Ngân hàng</label>
                    <select name="ngan_hang" class="form-control">
                        <option value="">Chọn ngân hàng</option>
                        @foreach(($managerBankOptions ?? []) as $bankCode => $bankLabel)
                            <option value="{{ $bankCode }}" {{ old('ngan_hang', $storeProfile?->ngan_hang) === $bankCode ? 'selected' : '' }}>
                                {{ $bankLabel }} ({{ $bankCode }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>
    @elseif($user->vai_tro === 'nhân viên')
    <div class="card mb-20">
        <div class="card-header">
            <span class="card-title">Thông tin nhân viên</span>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Mã nhân viên</label>
                    <input type="text" name="ma_nhan_vien" class="form-control"
                           value="{{ old('ma_nhan_vien', $staffProfile?->ma_nhan_vien) }}"
                           placeholder="Để trống để tự sinh mã">
                </div>
                <div class="form-group">
                    <label class="form-label">Chức vụ</label>
                    <select name="chuc_vu_id" class="form-control">
                        <option value="">-- Chọn chức vụ --</option>
                        @foreach($positions ?? [] as $position)
                            <option value="{{ $position->id }}" {{ (string) old('chuc_vu_id', $staffProfile?->chuc_vu_id) === (string) $position->id ? 'selected' : '' }}>
                                {{ $position->ten_chuc_vu }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Lương cơ bản</label>
                    <input type="number" min="0" step="1000" name="luong_co_ban" class="form-control"
                           value="{{ old('luong_co_ban', $staffProfile?->luong_co_ban) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Ngày vào làm</label>
                    <input type="date" name="ngay_vao_lam" class="form-control"
                           value="{{ old('ngay_vao_lam', optional($staffProfile?->ngay_vao_lam)->format('Y-m-d')) }}">
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="card mb-20">
        <div class="card-header">
            <span class="card-title">Thông tin khách hàng</span>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Giới tính</label>
                    <select name="gioi_tinh" class="form-control">
                        <option value="">Chọn giới tính</option>
                        <option value="nam" {{ old('gioi_tinh', $customerProfile?->gioi_tinh) === 'nam' ? 'selected' : '' }}>Nam</option>
                        <option value="nữ" {{ old('gioi_tinh', $customerProfile?->gioi_tinh) === 'nữ' ? 'selected' : '' }}>Nữ</option>
                        <option value="khác" {{ old('gioi_tinh', $customerProfile?->gioi_tinh) === 'khác' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Ngày sinh</label>
                    <input type="date" name="ngay_sinh" class="form-control"
                           value="{{ old('ngay_sinh', optional($customerProfile?->ngay_sinh)->format('Y-m-d')) }}">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Địa chỉ</label>
                    <textarea name="dia_chi" class="form-control" rows="3">{{ old('dia_chi', $customerProfile?->dia_chi) }}</textarea>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body" style="display: flex; justify-content: flex-end; gap: 10px;">
            <a href="{{ route('manager.dashboard') }}" class="btn btn-secondary">Quay lại</a>
            <button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
        </div>
    </div>
</form>
@endsection
