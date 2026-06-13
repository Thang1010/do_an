@extends('customer.layout.app')

@section('title', 'Hồ sơ cá nhân - XM Coffee')
@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
<style>
.pf-input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid rgba(255,255,255,0.15);
    border-radius: 10px;
    font-size: .93rem;
    color: #F5EFE4;
    background: rgba(255,255,255,0.06);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
}
.pf-input:focus { border-color: #C8A97A; box-shadow: 0 0 0 3px rgba(200,169,122,.15); }
.pf-input[disabled] { opacity: 0.5; cursor: not-allowed; }
.pf-input::placeholder { color: rgba(255,255,255,0.35); }
.pf-label { display: block; font-size: .82rem; font-weight: 600; color: rgba(255,255,255,0.7); margin-bottom: 6px; }
.pf-label span { color: #FCA5A5; }
.pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media(max-width:600px) { .pf-grid { grid-template-columns: 1fr; } }
.pf-full { grid-column: 1 / -1; }
.pf-card { background: rgba(30, 17, 6, 0.5); border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(14px); margin-bottom: 20px; }
.pf-card-title { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; color: #fff; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.pf-save-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 28px; background: #059669; color: #fff; border: none; border-radius: 10px; font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .2s; }
.pf-save-btn:hover { background: #047857; }
.pf-cancel-link { display: inline-flex; align-items: center; color: rgba(255,255,255,0.6); font-size: .9rem; text-decoration: none; transition: color .2s; }
.pf-cancel-link:hover { color: #fff; }
.avatar-img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(200,169,122,0.5); box-shadow: 0 2px 12px rgba(0,0,0,.4); flex-shrink: 0; cursor: pointer; transition: border-color .2s; }
.avatar-img:hover { border-color: #C8A97A; }
.avatar-pick-btn { display: inline-block; margin-top: 10px; padding: 8px 20px; background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; font-size: .88rem; cursor: pointer; transition: background .2s; }
.avatar-pick-btn:hover { background: rgba(255,255,255,0.2); }
.alert-success { background: rgba(5,150,105,0.15); border: 1px solid rgba(5,150,105,0.4); color: #6ee7b7; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .9rem; }
.alert-error { background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.4); color: #FCA5A5; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .9rem; }
textarea.pf-input { resize: vertical; }
select.pf-input option { background: #1E1106; color: #F5EFE4; }
</style>
@endpush

@section('content')
<main class="cart-main">
    <div class="cart-container" style="max-width: 800px;">

        {{-- Page heading --}}
        <div style="margin-bottom: 24px; text-align: center;">
            <h1 style="font-family: 'Playfair Display', serif; font-size: 1.9rem; font-weight: 700; color: #fff; margin: 0 0 4px;">Hồ sơ cá nhân</h1>
            <p style="color: rgba(255,255,255,0.55); font-size: 0.9rem; margin: 0;">Cập nhật ảnh đại diện và thông tin tài khoản của bạn</p>
        </div>

        @if(session('success'))
            <div class="alert-success">✓ {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">
                <strong>Có lỗi xảy ra:</strong>
                <ul style="margin:6px 0 0 16px;padding:0;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Main profile form --}}
        <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Avatar --}}
            <div class="pf-card">
                <p class="pf-card-title">Ảnh đại diện</p>
                <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                    <img id="avatar-preview"
                         src="{{ $user->avatar_url }}"
                         alt="Ảnh đại diện"
                         class="avatar-img"
                         onclick="document.getElementById('avatar-input').click()">
                    <div>
                        <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display:none;">
                        <button type="button" class="avatar-pick-btn" onclick="document.getElementById('avatar-input').click()">
                            Chọn ảnh mới
                        </button>
                        <p style="font-size: .82rem; color: rgba(255,255,255,0.45); margin: 6px 0 0;">JPG, PNG, GIF, WEBP — tối đa 2MB. Nhấp vào ảnh để chọn.</p>
                    </div>
                </div>
            </div>

            {{-- Thông tin tài khoản --}}
            <div class="pf-card">
                <p class="pf-card-title">Thông tin tài khoản</p>
                <div class="pf-grid">
                    <div>
                        <label class="pf-label">Họ tên <span>*</span></label>
                        <input type="text" name="ho_ten" class="pf-input"
                               value="{{ old('ho_ten', $user->ho_ten) }}" required>
                    </div>
                    <div>
                        <label class="pf-label">Vai trò</label>
                        <input type="text" class="pf-input" value="Khách hàng" disabled>
                    </div>
                    <div>
                        <label class="pf-label">Email</label>
                        <input type="email" name="email" class="pf-input"
                               value="{{ old('email', $user->email) }}">
                    </div>
                    <div>
                        <label class="pf-label">Số điện thoại</label>
                        <input type="text" name="so_dien_thoai" class="pf-input"
                               value="{{ old('so_dien_thoai', $user->so_dien_thoai) }}">
                    </div>
                </div>
            </div>

            {{-- Thông tin cá nhân --}}
            <div class="pf-card">
                <p class="pf-card-title">Thông tin cá nhân</p>
                <div class="pf-grid">
                    <div>
                        <label class="pf-label">Giới tính</label>
                        <select name="gioi_tinh" class="pf-input">
                            <option value="">Chọn giới tính</option>
                            <option value="nam" {{ old('gioi_tinh', $profile?->gioi_tinh) === 'nam' ? 'selected' : '' }}>Nam</option>
                            <option value="nữ" {{ old('gioi_tinh', $profile?->gioi_tinh) === 'nữ' ? 'selected' : '' }}>Nữ</option>
                            <option value="khác" {{ old('gioi_tinh', $profile?->gioi_tinh) === 'khác' ? 'selected' : '' }}>Khác</option>
                        </select>
                    </div>
                    <div>
                        <label class="pf-label">Ngày sinh</label>
                        <input type="date" name="ngay_sinh" class="pf-input" style="color-scheme: dark;"
                               value="{{ old('ngay_sinh', optional($profile?->ngay_sinh)->format('Y-m-d')) }}">
                    </div>
                    <div class="pf-full">
                        <label class="pf-label">Địa chỉ</label>
                        <textarea name="dia_chi" rows="3" class="pf-input">{{ old('dia_chi', $profile?->dia_chi) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-bottom: 20px;">
                <a href="{{ route('home') }}" class="pf-cancel-link">Huỷ</a>
                <button type="submit" class="pf-save-btn">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Lưu hồ sơ
                </button>
            </div>
        </form>



    </div>
</main>

@push('scripts')
<script>
document.getElementById('avatar-input')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => document.getElementById('avatar-preview').src = e.target.result;
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection