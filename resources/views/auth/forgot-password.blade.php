<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - XM Coffee</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="flex items-center justify-center gap-3 mb-8">
                <div style="width:52px;height:52px;border-radius:50%;background:#2a1f14;border:2px solid #5a3e2b;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width:40px;height:40px;object-fit:contain;" onerror="this.parentElement.innerHTML='☕'"/>
                </div>
                <span style="font-family:'Outfit',sans-serif;font-weight:700;font-size:20px;color:#F1F0EE;letter-spacing:0.08em;">XM COFFEE</span>
            </div>

            <h1 style="font-family:'Playfair Display',serif;font-weight:700;font-size:26px;color:#F1F0EE;text-align:center;font-style:italic;margin-bottom:12px;">Quên mật khẩu?</h1>
            <p style="color:#7a6555;font-size:14px;text-align:center;margin-bottom:24px;font-family:'Outfit',sans-serif;">Nhập email hoặc số điện thoại — chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu.</p>

            @if(session('status'))
                <div class="auth-alert-success">✅ {{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            @endif

            <form method="POST" action="{{ route('auth.forgot-password.post') }}">
                @csrf
                <div style="margin-bottom:8px;">
                    <input type="text" name="login" class="auth-input"
                           placeholder="Email hoặc số điện thoại"
                           value="{{ old('login') }}" required/>
                </div>
                <button type="submit" class="auth-btn-primary">Gửi yêu cầu đặt lại mật khẩu</button>
            </form>

            <p style="color:#C4B9A8;font-size:14px;text-align:center;margin-top:20px;font-family:'Outfit',sans-serif;">
                Nhớ mật khẩu rồi? <a href="{{ route('auth.login') }}" style="color:#F1F0EE;font-weight:600;border-bottom:1px solid #8D5D5D;text-decoration:none;">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
