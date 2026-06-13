<!-- ===== Navbar ===== -->
<div class="flex items-center justify-between py-6">
    <!-- Logo -->
    <a href="{{ route('home') }}" class="flex items-center" aria-label="XM Coffee - Trang chủ">
        <div class="relative flex items-center">
            <img src="{{ asset('images/logo.png') }}"
                class="w-[143px] md:w-[207px] h-auto object-contain z-0" alt="XM Coffee Logo" />
        </div>
    </a>

    <!-- Nav Links (Desktop) -->
    <nav class="hidden md:flex items-center gap-6 lg:gap-10">
        <a href="{{ route('home') }}" id="nav-home" class="nav-active font-outfit">Trang chủ</a>
        <a href="{{ route('menu.index') }}" class="nav-link">Menu</a>
        <a href="{{ route('home.about') }}" class="nav-link">Về chúng tôi</a>
        <a href="{{ route('home.contact') }}" class="nav-link">Liên hệ</a>
    </nav>

    <!-- Right actions (Desktop) -->
    <div class="hidden lg:flex items-center gap-4">
        <!-- Search -->
        <form action="{{ route('menu.index') }}" method="GET" class="relative">
            <input id="search-input" type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..."
                class="bg-[#D9D9D9] text-gray-800 text-sm py-3 px-5 pr-12 rounded-full w-60 focus:outline-none focus:ring-2 focus:ring-[#8D5D5D] font-outfit">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <button type="submit" class="absolute right-4 top-3" aria-label="Tìm kiếm">
                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </form>
        <!-- Cart icon -->
        <a href="{{ route('cart.index') }}" id="cart-btn" aria-label="Giỏ hàng"
            class="relative text-white hover:text-[#F0EADC] transition">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="cart-count-badge" style="display:none;position:absolute;top:-6px;right:-6px;background:#c94040;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:700;align-items:center;justify-content:center;line-height:1;">0</span>
        </a>
        @auth
            <!-- User dropdown -->
            <div class="relative" id="user-dropdown-wrapper">
                <button id="user-dropdown-btn"
                    class="flex items-center gap-2 bg-[#D9D9D9] text-[#30261C] font-poppins font-semibold text-sm py-2.5 px-5 rounded-full hover:bg-white transition shadow">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ Auth::user()->name }}
                </button>
                <div id="user-dropdown-menu"
                    class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100">
                    <a href="{{ route('customer.profile') }}"
                        class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Hồ sơ cá
                        nhân</a>
                    <a href="{{ route('customer.profile.password.edit') }}"
                        class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Đổi mật khẩu</a>
                    <a href="{{ route('customer.orders') }}"
                        class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Lịch sử
                        đơn hàng</a>
                    <hr class="my-1 border-[#E2D9C8]">
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-outfit">Đăng
                            xuất</button>
                    </form>
                </div>
            </div>
        @else
            <!-- Login / Register -->
            <a href="{{ route('auth.login') }}" id="login-btn"
                class="bg-[#D9D9D9] text-[#8D5D5D] font-poppins font-semibold text-base py-2.5 px-6 rounded-full hover:bg-white transition shadow">Đăng nhập</a>
            <a href="{{ route('auth.register') }}"
                class="bg-[#30261C] text-white font-poppins font-semibold text-base py-2.5 px-6 rounded-full hover:bg-black transition shadow">Đăng ký</a>
        @endauth
    </div>

    <!-- Mobile hamburger -->
    <div class="lg:hidden flex items-center gap-3">
        <a href="{{ route('cart.index') }}" id="cart-btn-mobile" aria-label="Giỏ hàng" class="relative text-white">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="cart-count-badge" style="display:none;position:absolute;top:-6px;right:-6px;background:#c94040;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1;">0</span>
        </a>
        <button id="mobile-menu-btn" aria-label="Menu"
            class="text-white hover:text-gray-200 focus:outline-none">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</div>

<!-- Mobile Menu Dropdown -->
<div id="mobile-menu">
    <nav class="flex flex-col gap-4">
        <a href="{{ route('home') }}"
            class="text-white font-outfit font-medium text-lg py-2 border-b border-white/10">Trang chủ</a>
        <a href="{{ route('menu.index') }}"
            class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Menu</a>
        <a href="{{ route('home.about') }}"
            class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Về
            chúng tôi</a>
        <a href="{{ route('home.contact') }}"
            class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Liên
            hệ</a>
        @auth
            <a href="{{ route('customer.profile') }}"
                class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Hồ
                sơ</a>
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button type="submit"
                    class="text-red-400 font-outfit font-medium text-lg py-2 w-full text-left">Đăng
                    xuất</button>
            </form>
        @else
            <a href="{{ route('auth.login') }}"
                class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Đăng
                nhập</a>
            <a href="{{ route('auth.register') }}"
                class="text-white/80 font-outfit font-medium text-lg py-2 hover:text-white">Đăng ký</a>
        @endauth
        <!-- Mobile Search -->
        <form action="{{ route('menu.index') }}" method="GET" class="mt-2 relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm..."
                class="w-full bg-white/10 text-white placeholder-white/60 text-base py-3 px-5 pr-12 rounded-full focus:outline-none focus:ring-2 focus:ring-white/40 font-outfit">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <button type="submit" class="absolute right-4 top-3.5" aria-label="Tìm kiếm">
                <svg class="w-5 h-5 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </form>
    </nav>
</div>
