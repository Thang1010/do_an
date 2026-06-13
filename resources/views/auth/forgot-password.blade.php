<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - XM Coffee</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth-forgot-password.css') }}">
</head>
<body>
<div class="auth-container">
    <div class="auth-image-panel">
        <img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=800&q=80" alt="Coffee"/>
        <div class="auth-overlay"></div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-back">
            <a href="{{ route('auth.login') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Đăng nhập
            </a>
        </div>
        <div class="w-full max-w-[380px]">
            <!-- Logo -->
            <div class="flex items-center justify-center gap-3 mb-6">
                <img src="{{ asset('images/logo.png') }}" alt="XM Coffee Logo" style="width: 330px; height: 150px; object-fit: contain;" onerror="this.style.display='none'; this.parentElement.innerHTML='<span style=\'font-family:\\\'Outfit\\\',sans-serif;font-weight:700;font-size:20px;color:#F1F0EE;letter-spacing:0.08em;\'>XM COFFEE</span>'">
            </div>

            <h1 style="font-family:'Playfair Display',serif;font-weight:700;font-size:32px;color:#F1F0EE;text-align:center;font-style:italic;margin-bottom:20px;">Quên mật khẩu?</h1>
            <p style="color:#7a6555;font-size:14px;text-align:center;margin-bottom:24px;font-family:'Outfit',sans-serif;">Nhập email đã đăng ký để nhận mã xác thực đặt lại mật khẩu.</p>

            @if(session('status'))
                <div class="auth-alert-success">✅ {{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            @endif

            <form method="POST" action="{{ route('auth.forgot-password.post') }}">
                @csrf
                <div style="margin-bottom:8px;">
                    <input type="email" name="email" class="auth-input"
                           placeholder="Email"
                           value="{{ old('email', $prefillEmail ?? '') }}" required/>
                </div>
                <button type="submit" class="auth-btn-primary">Gửi mã xác thực</button>
            </form>

            <div class="auth-divider">
                <div class="auth-divider-line"></div>
                <span class="auth-divider-text">hoặc</span>
                <div class="auth-divider-line"></div>
            </div>

            <a class="auth-btn-google" href="{{ route('auth.google.redirect', ['intent' => 'forgot']) }}">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Tiếp tục với Google
            </a>

            <p style="color:#C4B9A8;font-size:14px;text-align:center;margin-top:20px;font-family:'Outfit',sans-serif;">
                Nhớ mật khẩu rồi? <a href="{{ route('auth.login') }}" style="color:#F1F0EE;font-weight:600;border-bottom:1px solid #8D5D5D;text-decoration:none;">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
