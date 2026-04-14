<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - XM Coffee</title>
    <meta name="description" content="Tạo tài khoản XM Coffee để đặt món và nhận ưu đãi độc quyền.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth-register.css') }}">
</head>
<body>
<div class="auth-container">

    <!-- ===== Left: Image Panel ===== -->
    <div class="auth-image-panel">
        <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&q=80"
             alt="XM Coffee Barista"/>
        <div class="auth-image-overlay"></div>
        <div class="absolute top-12 left-8 right-8">
            <div class="flex items-center gap-3 mb-4">
                <div style="width:44px;height:44px;border-radius:50%;background:#2a1f14;border:2px solid #5a3e2b;display:flex;align-items:center;justify-content:center;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width:32px;height:32px;object-fit:contain;" onerror="this.parentElement.innerHTML='☕'"/>
                </div>
                <span style="font-family:'Outfit',sans-serif;font-weight:700;font-size:18px;color:#F1F0EE;letter-spacing:0.08em;">XM COFFEE</span>
            </div>
        </div>
        <div class="absolute bottom-12 left-8 right-8 text-white">
            <p class="font-playfair text-2xl italic leading-relaxed opacity-90">"Tham gia cùng chúng tôi,<br>nhận ngay ưu đãi 15%."</p>
            <p class="text-white/60 text-sm mt-3 font-outfit">— XM Coffee Members</p>
        </div>
    </div>

    <!-- ===== Right: Register Form ===== -->
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

        <div class="w-full max-w-[440px] py-8">

            <!-- Logo -->
            <div class="auth-logo justify-center">
                <div class="auth-logo-circle">
                    <img src="{{ asset('images/logo.png') }}" alt="XM Coffee Logo" class="w-10 h-10 object-contain"
                         onerror="this.style.display='none'; this.parentElement.innerHTML='☕'"/>
                </div>
                <span class="auth-logo-text">XM COFFEE</span>
            </div>

            <h1 class="auth-title">Đăng ký</h1>

            <!-- Error messages -->
            @if($errors->any())
                <div class="auth-alert">
                    @foreach($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
            @endif

            <!-- Register Form -->
            <form id="register-form" method="POST" action="{{ route('auth.register.post') }}" onsubmit="handleSubmit(this)">
                @csrf

                <!-- Hidden role field -->
                <input type="hidden" id="selected-role" name="vai_tro" value="{{ old('vai_tro', 'khách hàng') }}">

                <!-- Role Selector -->
                <div class="mb-2">
                    <p class="text-[#7a6555] text-xs font-outfit mb-2 text-center">Chọn loại tài khoản</p>
                    <div class="role-selector">
                        <div class="role-card active" id="role-card-customer" onclick="selectRole('khách hàng')">
                            <div class="role-card-icon">☕</div>
                            <div class="role-card-label">Khách hàng</div>
                        </div>
                        <div class="role-card" id="role-card-staff" onclick="selectRole('nhân viên')">
                            <div class="role-card-icon">🧑‍🍳</div>
                            <div class="role-card-label">Nhân viên</div>
                        </div>
                        <div class="role-card" id="role-card-manager" onclick="selectRole('quản lý')">
                            <div class="role-card-icon">👔</div>
                            <div class="role-card-label">Quản lý</div>
                        </div>
                    </div>
                </div>

                <!-- Notice for staff/manager -->
                <div class="role-notice" id="role-notice">
                    <strong>⚠️ Lưu ý:</strong> Tài khoản nhân viên/quản lý cần mã xác thực từ cửa hàng.
                </div>

                <!-- Staff/Manager code field -->
                <div class="auth-input-group staff-code-field" id="staff-code-field">
                    <input type="text" name="ma_xac_thuc" id="ma-xac-thuc-input"
                           class="auth-input @error('ma_xac_thuc') is-invalid @enderror"
                           placeholder="Mã xác thực nhân viên"
                           value="{{ old('ma_xac_thuc') }}"/>
                    @error('ma_xac_thuc')
                        <p class="auth-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Full name -->
                <div class="auth-input-group">
                    <input type="text" name="ho_ten" id="register-name"
                           class="auth-input @error('ho_ten') is-invalid @enderror"
                           placeholder="Họ và tên đầy đủ"
                           value="{{ old('ho_ten') }}"
                           required/>
                    @error('ho_ten')
                        <p class="auth-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email + Phone side by side -->
                <div class="auth-grid-2">
                    <div class="auth-input-group" style="margin-bottom:0">
                        <input type="email" name="email" id="register-email"
                               class="auth-input @error('email') is-invalid @enderror"
                               placeholder="Email"
                               value="{{ old('email') }}"/>
                        @error('email')
                            <p class="auth-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="auth-input-group" style="margin-bottom:0">
                        <input type="tel" name="so_dien_thoai" id="register-phone"
                               class="auth-input @error('so_dien_thoai') is-invalid @enderror"
                               placeholder="Số điện thoại"
                               value="{{ old('so_dien_thoai') }}"
                               pattern="[0-9]{9,11}"/>
                        @error('so_dien_thoai')
                            <p class="auth-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="text-[#7a6555] text-xs font-outfit mb-3 mt-1">*Nhập ít nhất một trong hai: Email hoặc Số điện thoại</p>

                <!-- Password -->
                <div class="auth-input-group">
                    <input type="password" name="password" id="register-password"
                           class="auth-input @error('password') is-invalid @enderror"
                           placeholder="Mật khẩu (ít nhất 8 ký tự)"
                           oninput="checkPasswordStrength(this.value)"
                           autocomplete="new-password"
                           required/>
                    <button type="button" class="auth-input-icon" onclick="togglePassword('register-password', this)" aria-label="Hiện mật khẩu">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    @error('password')
                        <p class="auth-field-error">{{ $message }}</p>
                    @enderror
                    <!-- Password strength indicator -->
                    <div class="password-strength-bar">
                        <div class="password-strength-fill" id="strength-fill"></div>
                    </div>
                    <p class="text-[#7a6555] text-xs font-outfit mt-1" id="strength-text"></p>
                </div>

                <!-- Confirm Password -->
                <div class="auth-input-group">
                    <input type="password" name="password_confirmation" id="register-password-confirm"
                           class="auth-input"
                           placeholder="Xác nhận mật khẩu"
                           autocomplete="new-password"
                           required/>
                    <button type="button" class="auth-input-icon" onclick="togglePassword('register-password-confirm', this)" aria-label="Hiện mật khẩu">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>

                <!-- Terms checkbox -->
                <div class="flex items-start gap-2 mb-4">
                    <input type="checkbox" id="terms" name="terms" required
                           class="w-4 h-4 mt-0.5 accent-[#8D5D5D] cursor-pointer flex-shrink-0">
                    <label for="terms" class="auth-terms text-left" style="text-align:left;margin:0;color:#7a6555;">
                        Tôi đồng ý với <a href="#">Điều khoản sử dụng</a> và <a href="#">Chính sách bảo mật</a> của XM Coffee.
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" id="register-submit-btn" class="auth-btn-primary">
                    Tạo tài khoản
                </button>
            </form>

            <!-- OR divider -->
            <div class="auth-divider">
                <div class="auth-divider-line"></div>
                <span class="auth-divider-text">or</span>
                <div class="auth-divider-line"></div>
            </div>

            <!-- Google Register (customers only) -->
            <button class="auth-btn-google" onclick="alert('Tính năng đang phát triển')">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Đăng ký bằng Google
            </button>

            <!-- Login link -->
            <p class="auth-link mt-6">
                Đã có tài khoản? <a href="{{ route('auth.login') }}">Đăng nhập</a>
            </p>

        </div>
    </div>
</div>

<script>
    // Role selector
    const roleCards = {
        'khách hàng': document.getElementById('role-card-customer'),
        'nhân viên': document.getElementById('role-card-staff'),
        'quản lý': document.getElementById('role-card-manager'),
    };
    const roleInput = document.getElementById('selected-role');
    const roleNotice = document.getElementById('role-notice');
    const staffCodeField = document.getElementById('staff-code-field');
    const staffCodeInput = document.getElementById('ma-xac-thuc-input');

    function selectRole(role) {
        // Remove active from all
        Object.values(roleCards).forEach(card => card.classList.remove('active'));
        // Activate selected
        roleCards[role].classList.add('active');
        roleInput.value = role;

        // Show/hide staff notice and code field
        if (role === 'nhân viên' || role === 'quản lý') {
            roleNotice.classList.add('visible');
            staffCodeField.classList.add('visible');
            staffCodeInput.required = true;
        } else {
            roleNotice.classList.remove('visible');
            staffCodeField.classList.remove('visible');
            staffCodeInput.required = false;
        }
    }

    // Initialize based on old() value
    const initialRole = '{{ old('vai_tro', 'khách hàng') }}';
    if (initialRole && initialRole !== 'khách hàng') {
        selectRole(initialRole);
    }

    // Toggle password visibility
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

    // Password strength
    function checkPasswordStrength(value) {
        const fill = document.getElementById('strength-fill');
        const text = document.getElementById('strength-text');
        let strength = 0;
        if (value.length >= 8) strength++;
        if (/[A-Z]/.test(value)) strength++;
        if (/[0-9]/.test(value)) strength++;
        if (/[^A-Za-z0-9]/.test(value)) strength++;

        if (value.length === 0) {
            fill.style.width = '0%';
            fill.className = 'password-strength-fill';
            text.textContent = '';
        } else if (strength <= 1) {
            fill.style.width = '25%';
            fill.className = 'password-strength-fill strength-weak';
            text.textContent = 'Yếu — thêm chữ hoa, số, ký tự đặc biệt';
        } else if (strength === 2) {
            fill.style.width = '55%';
            fill.className = 'password-strength-fill strength-medium';
            text.textContent = 'Trung bình';
        } else {
            fill.style.width = '100%';
            fill.className = 'password-strength-fill strength-strong';
            text.textContent = 'Mạnh ✓';
        }
    }

    // Submit loading state
    function handleSubmit(form) {
        // Validate passwords match
        const p1 = document.getElementById('register-password').value;
        const p2 = document.getElementById('register-password-confirm').value;
        if (p1 !== p2) {
            alert('Mật khẩu xác nhận không khớp!');
            return false;
        }
        const btn = document.getElementById('register-submit-btn');
        btn.textContent = 'Đang tạo tài khoản...';
        btn.classList.add('loading');
    }
</script>
</body>
</html>
