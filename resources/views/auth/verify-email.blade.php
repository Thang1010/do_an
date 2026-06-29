<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh email - XM Coffee</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_web.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo_web.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth-forgot-password.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-override.css') }}?v={{ filemtime(public_path('css/font-override.css')) }}">
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
            <div class="flex items-center justify-center gap-3 mb-6">
                <img src="{{ asset('images/logo.png') }}" alt="XM Coffee Logo" style="width: 330px; height: 150px; object-fit: contain;" onerror="this.style.display='none'; this.parentElement.innerHTML='<span style=\'font-family:\\\'Outfit\\\',sans-serif;font-weight:700;font-size:20px;color:#F1F0EE;letter-spacing:0.08em;\'>XM COFFEE</span>'">
            </div>

            <h1 style="font-family:'Playfair Display',serif;font-weight:700;font-size:32px;color:#F1F0EE;text-align:center;font-style:italic;margin-bottom:20px;">Xác minh email</h1>
            <p style="color:#7a6555;font-size:14px;text-align:center;margin-bottom:24px;font-family:'Outfit',sans-serif;">Nhập mã xác minh đã được gửi đến email của bạn.</p>

            @if(session('success'))
                <div class="auth-alert-success">✅ {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            @endif

            <form method="POST" action="{{ route('auth.verify-email.post') }}">
                @csrf
                <div style="margin-bottom:8px;">
                          <input id="verify-email-input" type="email" name="email" class="auth-input"
                           placeholder="Email" value="{{ old('email', $prefillEmail ?? '') }}" required/>
                </div>
                <div style="margin-bottom:8px;">
                    <input type="text" name="code" class="auth-input"
                           placeholder="Mã xác minh (6 chữ số)" value="{{ old('code') }}" required/>
                </div>
                <button type="submit" class="auth-btn-primary">Xác minh</button>
            </form>

            <div class="auth-divider">
                <div class="auth-divider-line"></div>
                <span class="auth-divider-text">or</span>
                <div class="auth-divider-line"></div>
            </div>

            <form method="POST" action="{{ route('auth.verify-email.resend') }}" id="resend-code-form">
                @csrf
                <input type="hidden" name="email" id="resend-email-input" value="{{ old('email', $prefillEmail ?? '') }}" />
                <button type="submit" class="auth-btn-google">Gửi lại mã xác minh</button>
            </form>
        </div>
    </div>
</div>

<script>
    const resendForm = document.getElementById('resend-code-form');
    const resendEmailInput = document.getElementById('resend-email-input');
    const verifyEmailInput = document.getElementById('verify-email-input');

    if (resendForm && resendEmailInput && verifyEmailInput) {
        resendForm.addEventListener('submit', function () {
            resendEmailInput.value = verifyEmailInput.value || resendEmailInput.value;
        });
    }
</script>
</body>
</html>
