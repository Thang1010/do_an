@extends('manager.layout.app')

@php
    $selectedRole = $selectedRole ?? old('vai_tro_ap_dung', 'nhân viên');
@endphp

@section('title', 'Thêm chức vụ')
@section('breadcrumb', 'Nhân sự / <a href="' . route('manager.positions.index') . '">Quản lý chức vụ</a> / <strong>Thêm mới</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Thêm chức vụ</h1>
        <p class="page-subtitle">Tạo mới chức vụ cho nhân viên hoặc quản lý</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

<div class="card" style="max-width: 900px;">
    <div class="card-header">
        <span class="card-title">Thông tin chức vụ</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('manager.positions.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Tên chức vụ <span>*</span></label>
                <input type="text" name="ten_chuc_vu" class="form-control" maxlength="100" value="{{ old('ten_chuc_vu') }}" required>
                @error('ten_chuc_vu')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Vai trò áp dụng <span>*</span></label>
                <select name="vai_tro_ap_dung" class="form-control" required>
                    @foreach(($allowedRoles ?? ['nhân viên' => 'Nhân viên', 'quản lý' => 'Quản lý']) as $value => $label)
                        <option value="{{ $value }}" {{ $selectedRole === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('vai_tro_ap_dung')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea name="mo_ta_chuc_vu" class="form-control" rows="4" maxlength="1000">{{ old('mo_ta_chuc_vu') }}</textarea>
                @error('mo_ta_chuc_vu')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
                <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">Lưu chức vụ</button>
            </div>
        </form>
    </div>
</div>

@endsection
