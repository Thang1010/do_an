@extends('customer.layout.app')

@section('title', 'Đổi mật khẩu - XM Coffee')
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
.alert-success { background: rgba(5,150,105,0.15); border: 1px solid rgba(5,150,105,0.4); color: #6ee7b7; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .9rem; }
.alert-error { background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.4); color: #FCA5A5; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .9rem; }
</style>
@endpush

@section('content')
<main class="cart-main">
    <div class="cart-container" style="max-width: 800px;">

        {{-- Page heading --}}
        <div style="margin-bottom: 24px; text-align: center;">
            <h1 style="font-family: 'Playfair Display', serif; font-size: 1.9rem; font-weight: 700; color: #fff; margin: 0 0 4px;">Đổi mật khẩu</h1>
            <p style="color: rgba(255,255,255,0.55); font-size: 0.9rem; margin: 0;">Cập nhật mật khẩu bảo mật cho tài khoản của bạn</p>
        </div>

        @if(session('success'))
            <div class="alert-success">✓ {{ session('success') }}</div>
        @endif
        @if($errors->has('current_password'))
            <div class="alert-error" style="margin-bottom:16px;">{{ $errors->first('current_password') }}</div>
        @endif
        @if($errors->any() && !$errors->has('current_password'))
            <div class="alert-error">
                <strong>Có lỗi xảy ra:</strong>
                <ul style="margin:6px 0 0 16px;padding:0;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Đổi mật khẩu --}}
        <div class="pf-card">
            <form method="POST" action="{{ route('customer.profile.password') }}">
                @csrf
                @method('PUT')
                <div class="pf-grid">
                    <div class="pf-full">
                        <label class="pf-label">Mật khẩu hiện tại <span>*</span></label>
                        <input type="password" name="current_password" class="pf-input" required>
                    </div>
                    <div>
                        <label class="pf-label">Mật khẩu mới <span>*</span></label>
                        <input type="password" name="new_password" class="pf-input" required minlength="6">
                    </div>
                    <div>
                        <label class="pf-label">Xác nhận mật khẩu mới <span>*</span></label>
                        <input type="password" name="new_password_confirmation" class="pf-input" required minlength="6">
                    </div>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 24px;">
                    <a href="{{ route('home') }}" class="pf-cancel-link">Huỷ</a>
                    <button type="submit" class="pf-save-btn">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Cập nhật mật khẩu
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
@endsection
