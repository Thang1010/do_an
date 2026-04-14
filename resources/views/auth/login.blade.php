<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - XM Coffee</title>
    <meta name="description" content="Đăng nhập vào tài khoản XM Coffee của bạn.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
</head>
<body>
<div class="auth-container">

    <!-- ===== Left: Coffee Image ===== -->
    <div class="auth-image-panel">
        <img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=800&q=80"
             alt="XM Coffee - Cà phê thơm ngon"/>
        <div class="auth-image-overlay"></div>
        <!-- Decorative quote -->
        <div class="absolute bottom-12 left-8 right-8 text-white">
            <p class="font-playfair text-2xl italic leading-relaxed opacity-90">"Một tách cà phê mỗi sáng,<br>làm nên một ngày tuyệt vời."</p>
            <p class="text-white/60 text-sm mt-3 font-outfit">— XM Coffee</p>
        </div>
    </div>

    <!-- ===== Right: Login Form ===== -->
    <div class="auth-form-panel">

        <!-- Back to home -->
        <div class="auth-back">
            <a href="{{ route('home') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Trang chủ
            </a>
        </div>

        <div class="w-full max-w-[380px]">

            <!-- Logo -->
            <div class="auth-logo justify-center">
                <div class="auth-logo-circle">
                    <img src="{{ asset('images/logo.png') }}" alt="XM Coffee Logo" class="w-10 h-10 object-contain"
                         onerror="this.style.display='none'; this.parentElement.innerHTML='☕'"/>
                </div>
                <span class="auth-logo-text">XM COFFEE</span>
            </div>

            <!-- Title -->
            <h1 class="auth-title">Đăng nhập</h1>

            <!-- Error messages -->
            @if($errors->any())
                <div class="auth-alert">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('error'))
                <div class="auth-alert">{{ session('error') }}</div>
            @endif

            @if(session('success'))
                <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
            @endif

            <!-- Login Form -->
            <form id="login-form" method="POST" action="{{ route('auth.login.post') }}" onsubmit="handleSubmit(this)">
                @csrf

                <!-- Email / Phone -->
                <div class="auth-input-group">
                    <input id="login-email" type="text" name="login"
                           class="auth-input @error('login') is-invalid @enderror"
                           placeholder="Email hoặc số điện thoại"
                           value="{{ old('login') }}"
                           autocomplete="username"
                           required/>
                    @error('login')
                        <p class="auth-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="auth-input-group">
                    <input id="login-password" type="password" name="password"
                           class="auth-input @error('password') is-invalid @enderror"
                           placeholder="Mật khẩu"
                           autocomplete="current-password"
                           required/>
                    <button type="button" class="auth-input-icon" onclick="togglePassword('login-password', this)" aria-label="Hiện mật khẩu">
                        <svg id="eye-icon-login" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    @error('password')
                        <p class="auth-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Forgot password -->
                <div class="auth-forgot">
                    <a href="{{ route('auth.forgot-password') }}">Quên mật khẩu?</a>
                </div>

                <!-- Remember me -->
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" id="remember" name="remember" value="1"
                           class="w-4 h-4 accent-[#8D5D5D] cursor-pointer rounded"
                           {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember" class="text-[#C4B9A8] text-sm font-outfit cursor-pointer">Ghi nhớ đăng nhập</label>
                </div>

                <!-- Submit -->
                <button type="submit" id="login-submit-btn" class="auth-btn-primary">
                    Đăng nhập
                </button>
            </form>

            <!-- OR divider -->
            <div class="auth-divider">
                <div class="auth-divider-line"></div>
                <span class="auth-divider-text">or</span>
                <div class="auth-divider-line"></div>
            </div>

            <!-- Google Login -->
            <button class="auth-btn-google" onclick="alert('Tính năng đang phát triển')">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Đăng nhập bằng Google
            </button>

            <!-- Register link -->
            <p class="auth-link mt-6">
                Chưa có tài khoản? <a href="{{ route('auth.register') }}">Đăng ký</a>
            </p>

        </div>
    </div>
</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.innerHTML = isHidden
            ? `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
               </svg>`
            : `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
               </svg>`;
    }

    function handleSubmit(form) {
        const btn = document.getElementById('login-submit-btn');
        btn.textContent = 'Đang đăng nhập...';
        btn.classList.add('loading');
    }
</script>
</body>
</html>
