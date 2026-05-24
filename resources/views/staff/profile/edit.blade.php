@extends('staff.layout.app')
@section('title', 'Hồ sơ cá nhân')
@section('breadcrumb', 'Hệ thống / <strong>Hồ sơ cá nhân</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Hồ sơ cá nhân</h1>
        <p class="page-subtitle">Cập nhật thông tin tài khoản và đổi mật khẩu</p>
    </div>
</div>

<form method="POST" action="{{ route('staff.profile.update') }}" class="mb-20">
    @csrf
    @method('PUT')
    <div class="card">
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
                    <input type="text" class="form-control" value="{{ ucfirst($user->vai_tro ?? '') }}" disabled>
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
        <div class="card-body" style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
        </div>
    </div>
</form>

<div class="card" id="password">
    <div class="card-header">
        <span class="card-title">Đổi mật khẩu</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('staff.profile.password') }}">
            @csrf
            @method('PUT')
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Mật khẩu hiện tại <span>*</span></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mật khẩu mới <span>*</span></label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Xác nhận mật khẩu mới <span>*</span></label>
                    <input type="password" name="new_password_confirmation" class="form-control" required minlength="6">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>
        </form>
    </div>
</div>
@endsection
