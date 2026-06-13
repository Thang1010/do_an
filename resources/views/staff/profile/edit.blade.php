@extends('staff.layout.app')
@section('title', 'Hồ sơ cá nhân')
@section('breadcrumb', 'Hệ thống / <strong>Hồ sơ cá nhân</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Hồ sơ cá nhân</h1>
        <p class="page-subtitle">Cập nhật ảnh đại diện, thông tin tài khoản và đổi mật khẩu</p>
    </div>
</div>

<form method="POST" action="{{ route('staff.profile.update') }}" class="mb-20" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Avatar --}}
    <div class="card mb-20">
        <div class="card-header">
            <span class="card-title">Ảnh đại diện</span>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <img id="avatar-preview"
                     src="{{ $user->avatar_url }}"
                     alt="Ảnh đại diện"
                     style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #E2D9C8; cursor:pointer; flex-shrink:0;"
                     onclick="document.getElementById('staff-avatar-input').click()">
                <div>
                    <input type="file" name="avatar" id="staff-avatar-input" accept="image/*" style="display:none;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('staff-avatar-input').click()">Chọn ảnh</button>
                    <p style="font-size:.8rem; color:#888; margin-top:6px;">JPG, PNG, GIF, WEBP — tối đa 2MB. Bấm "Lưu thay đổi" ở dưới cùng để cập nhật.</p>
                </div>
            </div>
        </div>
    </div>

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
                    <label class="form-label">Chức vụ</label>
                    <input type="text" class="form-control" value="{{ $user->hoSoNhanVien?->chucVu?->ten_chuc_vu ?? '—' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Ngày vào làm</label>
                    <input type="text" class="form-control" value="{{ optional($user->hoSoNhanVien?->ngay_vao_lam)->format('d/m/Y') ?? '—' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai', $user->so_dien_thoai) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Ngày sinh</label>
                    <input type="date" name="ngay_sinh" class="form-control" value="{{ old('ngay_sinh', optional($user->hoSoNhanVien?->ngay_sinh)->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="dia_chi_tam_chu" class="form-control" value="{{ old('dia_chi_tam_chu', $user->hoSoNhanVien?->dia_chi_tam_chu) }}">
                </div>
            </div>
        </div>
        <div class="card-body" style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
        </div>
    </div>
</form>



@push('scripts')
<script>
document.getElementById('staff-avatar-input')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => document.getElementById('avatar-preview').src = e.target.result;
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
