<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - XM Coffee</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth-forgot-password.css') }}">
</head>
<body>
@php
    $showPasswordForm = $isVerified ?? false;
@endphp
<div class="auth-container">
    <div class="auth-image-panel">
        <img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=800&q=80" alt="Coffee"/>
        <div class="auth-overlay"></div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-back">
            <a href="{{ route('auth.forgot-password') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Quên mật khẩu
            </a>
        </div>
        <div class="w-full max-w-[380px]">
            <div class="flex items-center justify-center gap-3 mb-6">
                <img src="{{ asset('images/logo.png') }}" alt="XM Coffee Logo" style="width: 330px; height: 150px; object-fit: contain;" onerror="this.style.display='none'; this.parentElement.innerHTML='<span style=\'font-family:\\\'Outfit\\\',sans-serif;font-weight:700;font-size:20px;color:#F1F0EE;letter-spacing:0.08em;\'>XM COFFEE</span>'">
            </div>

            <h1 style="font-family:'Playfair Display',serif;font-weight:700;font-size:32px;color:#F1F0EE;text-align:center;font-style:italic;margin-bottom:20px;">Đặt lại mật khẩu</h1>

            @if(!$showPasswordForm)
                <p style="color:#7a6555;font-size:14px;text-align:center;margin-bottom:24px;font-family:'Outfit',sans-serif;">Nhập mã xác thực đã gửi đến email {{ $email }}.</p>
            @else
                <p style="color:#7a6555;font-size:14px;text-align:center;margin-bottom:24px;font-family:'Outfit',sans-serif;">Mã xác thực hợp lệ. Vui lòng đặt mật khẩu mới.</p>
            @endif

            @if(session('status'))
                <div class="auth-alert-success">✅ {{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            @endif

            @if(!$showPasswordForm)
                <form method="POST" action="{{ route('auth.reset-password.verify') }}">
                    @csrf
                    <div style="margin-bottom:8px;">
                        <input type="text" name="code" class="auth-input"
                               placeholder="Mã xác thực (6 chữ số)"
                               inputmode="numeric" autocomplete="one-time-code" required/>
                    </div>
                    <button type="submit" class="auth-btn-primary">Xác minh mã</button>
                </form>

                <form method="POST" action="{{ route('auth.forgot-password.post') }}" style="margin-top:14px;">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}"/>
                    <button type="submit" class="auth-btn-google" style="justify-content:center;">
                        Gửi lại mã xác thực
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('auth.reset-password.post') }}">
                    @csrf
                    <div style="margin-bottom:8px;">
                        <input type="password" name="password" class="auth-input" placeholder="Mật khẩu mới" required/>
                    </div>
                    <div style="margin-bottom:8px;">
                        <input type="password" name="password_confirmation" class="auth-input" placeholder="Nhập lại mật khẩu" required/>
                    </div>
                    <button type="submit" class="auth-btn-primary">Lưu mật khẩu</button>
                </form>
            @endif
        </div>
    </div>
</div>
</body>
</html>