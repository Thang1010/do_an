<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>@yield('title', 'XM Coffee')</title>
	<meta name="description" content="@yield('meta_description', 'XM Coffee')">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link
		href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&family=Dancing+Script:wght@700&display=swap"
		rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/home.css') }}">
	<link rel="stylesheet" href="{{ asset('css/chatbot-fab.css') }}">
	
	@stack('styles')
	@yield('head')
</head>

<body class="@yield('body_class')">
	@php
		$showHeader = trim($__env->yieldContent('show_header', '1')) !== '0';
		$headerBg = trim($__env->yieldContent('header_bg', asset('images/background.png')));
		$headerOverlay = trim($__env->yieldContent('header_overlay', 'bg-black/20'));
		$headerStyle = trim($__env->yieldContent('header_style'));
		$showFooter = trim($__env->yieldContent('show_footer', '1')) !== '0';
	@endphp

	@if($showHeader)
		<header class="relative bg-cover bg-center bg-no-repeat"
			data-header-bg="{{ $headerBg }}"
			data-header-style="{{ $headerStyle }}">
			<div class="absolute inset-0 {{ $headerOverlay }}"></div>
			<div class="relative z-10 max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16">
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
						<a href="{{ route('home') }}"
							class="{{ request()->routeIs('home') ? 'nav-active' : 'nav-link' }} font-outfit">Trang chủ</a>
						<a href="{{ route('menu.index') }}"
							class="{{ request()->routeIs('menu.*') ? 'nav-active' : 'nav-link' }} font-outfit">Menu</a>
						<a href="{{ route('home.contact') }}"
							class="{{ request()->routeIs('home.contact') ? 'nav-active' : 'nav-link' }}">Liên hệ</a>
					</nav>

					<!-- Right actions (Desktop) -->
					<div class="hidden lg:flex items-center gap-4">
						<!-- Search -->
						<form action="{{ route('menu.index') }}" method="GET" class="relative">
							<input id="search-input" type="text" name="search" value="{{ request('search') }}"
								placeholder="Tìm kiếm..."
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
							<span class="cart-count-badge"
								style="display:none;position:absolute;top:-6px;right:-6px;background:#c94040;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:700;align-items:center;justify-content:center;line-height:1;">0</span>
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
							<span class="cart-count-badge"
								style="display:none;position:absolute;top:-6px;right:-6px;background:#c94040;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1;">0</span>
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
							class="{{ request()->routeIs('home') ? 'text-white font-outfit font-semibold text-lg' : 'text-white/80 font-outfit font-medium text-lg hover:text-white' }} py-2 border-b border-white/10">Trang chủ</a>
						<a href="{{ route('menu.index') }}"
							class="{{ request()->routeIs('menu.*') ? 'text-white font-outfit font-semibold text-lg' : 'text-white/80 font-outfit font-medium text-lg hover:text-white' }} py-2 border-b border-white/10">Menu</a>
						<a href="{{ route('home.contact') }}"
							class="{{ request()->routeIs('home.contact') ? 'text-white font-outfit font-semibold text-lg' : 'text-white/80 font-outfit font-medium text-lg hover:text-white' }} py-2 border-b border-white/10">Liên hệ</a>
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

				@yield('header_content')
			</div>
		</header>
	@endif

	@yield('content')

	@if(session('force_password_setup') && auth()->check())
		<div id="force-password-modal" class="force-password-modal" role="dialog" aria-modal="true" aria-label="Đặt mật khẩu XM COFFEE">
			<div class="force-password-modal__backdrop"></div>
			<div class="force-password-modal__panel">
				<div class="force-password-modal__title">Đặt mật khẩu XM COFFEE</div>
				<p class="force-password-modal__desc">Vui lòng đặt mật khẩu mới để hoàn tất đăng nhập.</p>
				@if($errors->any())
					<div class="force-password-modal__alert">
						@foreach($errors->all() as $error)
							<div>• {{ $error }}</div>
						@endforeach
					</div>
				@endif
				<form method="POST" action="{{ route('auth.password.setup.post') }}">
					@csrf
					<div class="force-password-modal__field">
						<label for="force-password">Mật khẩu mới</label>
						<input id="force-password" type="password" name="password" autocomplete="new-password" placeholder="Nhập mật khẩu mới" required>
					</div>
					<div class="force-password-modal__field">
						<label for="force-password-confirm">Nhập lại mật khẩu</label>
						<input id="force-password-confirm" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required>
					</div>
					<div class="force-password-modal__hint">Mật khẩu tối thiểu 8 ký tự.</div>
					<button type="submit" class="force-password-modal__submit">Lưu mật khẩu</button>
				</form>
			</div>
		</div>
	@endif

	@auth
		@if(auth()->user()->isKhachHang())
			@php
				$userId = auth()->id();
				$now = now();
				$vouchers = \App\Models\Voucher::where('trang_thai', 'hoạt động')
					->where('ngay_bat_dau', '<=', $now)
					->where('ngay_ket_thuc', '>=', $now)
					->get();
				$availableVouchers = $vouchers->filter(function($v) use ($userId) {
					return !\App\Models\VoucherNguoiDung::where('nguoi_dung_id', $userId)
							->where('voucher_id', $v->id)
							->exists();
				});
			@endphp
			@if($availableVouchers->isNotEmpty())
				<div id="voucher-popup-modal" class="force-password-modal is-open" style="z-index: 10000;">
					<div class="force-password-modal__backdrop" onclick="document.getElementById('voucher-popup-modal').classList.remove('is-open')"></div>
					<div class="force-password-modal__panel" style="background: #2a1f18; padding: 32px 24px;">
						<button type="button" onclick="document.getElementById('voucher-popup-modal').classList.remove('is-open')" style="position: absolute; right: 16px; top: 12px; color: #fff; font-size: 20px; font-weight: bold; background: none; border: none; cursor: pointer;">×</button>
						<div class="force-password-modal__title" style="color: #F0DDB8; font-size: 26px;">Quà Tặng Dành Cho Bạn!</div>
						<p class="force-password-modal__desc" style="margin-bottom: 24px;">Bạn có {{ $availableVouchers->count() }} voucher mới chưa nhận.</p>
						
						<div style="max-height: 300px; overflow-y: auto; margin-bottom: 8px; display: flex; flex-direction: column; gap: 12px; padding-right: 4px;">
							@foreach($availableVouchers as $v)
								<div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 16px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
									<div>
										<h4 style="font-weight: 700; color: #6ee7b7; font-size: 16px; margin: 0;">{{ $v->ma_voucher }}</h4>
										<p style="color: #fff; font-size: 13px; margin: 4px 0 0;">{{ $v->ten_voucher }} <br> <span style="opacity: 0.8; font-size: 12px;">(HSD: {{ \Carbon\Carbon::parse($v->ngay_ket_thuc)->format('d/m/Y') }})</span></p>
									</div>
									<form action="{{ route('customer.vouchers.claim', $v->id) }}" method="POST" style="margin: 0;">
										@csrf
										<button type="submit" style="background: #059669; color: #fff; padding: 8px 20px; border-radius: 50px; font-weight: bold; font-size: 13px; border: none; cursor: pointer;">Nhận</button>
									</form>
								</div>
							@endforeach
						</div>
					</div>
				</div>
			@endif
		@endif
	@endauth

	<a href="{{ route('chatbot.index') }}" class="chatbot-fab" aria-label="Mở trang chatbot">
		<span class="chatbot-fab-icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M8 10h8M8 14h5m-6 6l-3 3V7a3 3 0 013-3h10a3 3 0 013 3v6a3 3 0 01-3 3H7z" />
			</svg>
		</span>
		<span class="chatbot-fab-label">Tư vấn</span>
	</a>

	@if($showFooter)
		<footer class="bg-[#30261C] text-[#F1F0EE] py-12 border-t border-[#8D5D5D]/30 relative z-10 w-full mt-auto">
			<div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
				<div>
					<h3 class="font-playfair text-2xl font-bold mb-6 text-[#F0DDB8]">XM Coffee</h3>
					<p class="opacity-80 font-outfit text-sm leading-relaxed mb-6">
						Nơi mang đến những tách cà phê đậm đà và không gian thư giãn tuyệt vời. Thưởng thức và cảm nhận sự khác
						biệt.
					</p>
				</div>

				<div>
					<h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Liên kết nhanh</h3>
					<ul class="space-y-3 font-outfit text-sm opacity-80">
						<li><a href="{{ route('home') }}" class="hover:text-white transition">Trang chủ</a></li>
						<li><a href="{{ route('menu.index') }}" class="hover:text-white transition">Thực đơn</a></li>
						<li><a href="{{ route('home.about') }}" class="hover:text-white transition">Về chúng tôi</a></li>
						<li><a href="{{ route('home.contact') }}" class="hover:text-white transition">Liên hệ</a></li>
					</ul>
				</div>

				<div>
					<h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Chính sách</h3>
					<ul class="space-y-3 font-outfit text-sm opacity-80">
						<li><a href="#" class="hover:text-white transition">Điều khoản sử dụng</a></li>
						<li><a href="#" class="hover:text-white transition">Chính sách bảo mật</a></li>
						<li><a href="#" class="hover:text-white transition">Chính sách hoàn tiền</a></li>
					</ul>
				</div>

				<div>
					<h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Liên hệ</h3>
					<ul class="space-y-3 font-outfit text-sm opacity-80">
						<li class="flex items-center gap-3">
							<svg class="w-5 h-5 text-[#8D5D5D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
							</svg>
							<span>123 Đường Cà Phê, Quận 1, TP.HCM</span>
						</li>
						<li class="flex items-center gap-3 mt-2">
							<svg class="w-5 h-5 text-[#8D5D5D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
							</svg>
							<span>0123 456 789</span>
						</li>
					</ul>
				</div>
			</div>

			<div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 mt-12 pt-8 border-t border-white/10 text-center opacity-60 font-outfit text-sm">
				&copy; {{ date('Y') }} XM Coffee. All rights reserved.
			</div>
		</footer>
	@endif

	<style>
		.force-password-modal {
			position: fixed;
			inset: 0;
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 9999;
			padding: 20px;
		}
		.force-password-modal.is-open {
			display: flex;
		}
		.force-password-modal__backdrop {
			position: absolute;
			inset: 0;
			background: rgba(18, 12, 8, 0.75);
			backdrop-filter: blur(2px);
		}
		.force-password-modal__panel {
			position: relative;
			width: min(460px, 92vw);
			background: #1f1710;
			border: 1px solid rgba(141, 93, 93, 0.5);
			border-radius: 18px;
			padding: 28px 26px;
			box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
			color: #f1f0ee;
			font-family: 'Outfit', sans-serif;
		}
		.force-password-modal__title {
			font-family: 'Playfair Display', serif;
			font-size: 22px;
			font-weight: 700;
			text-align: center;
			margin-bottom: 8px;
		}
		.force-password-modal__desc {
			font-size: 14px;
			color: rgba(241, 240, 238, 0.72);
			text-align: center;
			margin-bottom: 18px;
		}
		.force-password-modal__alert {
			background: rgba(255, 107, 107, 0.15);
			border: 1px solid rgba(255, 107, 107, 0.4);
			color: #ffd6d6;
			padding: 10px 12px;
			border-radius: 12px;
			font-size: 13px;
			margin-bottom: 14px;
		}
		.force-password-modal__field {
			display: flex;
			flex-direction: column;
			gap: 6px;
			margin-bottom: 12px;
		}
		.force-password-modal__field label {
			font-size: 13px;
			color: rgba(241, 240, 238, 0.85);
		}
		.force-password-modal__field input {
			width: 100%;
			padding: 12px 14px;
			border-radius: 12px;
			border: 1px solid rgba(255, 255, 255, 0.12);
			background: rgba(255, 255, 255, 0.08);
			color: #f1f0ee;
			font-size: 14px;
			outline: none;
		}
		.force-password-modal__field input:focus {
			border-color: rgba(210, 160, 120, 0.7);
			box-shadow: 0 0 0 2px rgba(210, 160, 120, 0.2);
		}
		.force-password-modal__hint {
			font-size: 12px;
			color: rgba(241, 240, 238, 0.6);
			margin-bottom: 16px;
		}
		.force-password-modal__submit {
			width: 100%;
			padding: 12px 16px;
			border-radius: 999px;
			border: none;
			background: #c49a6c;
			color: #1a120c;
			font-weight: 600;
			letter-spacing: 0.02em;
			cursor: pointer;
		}
		.force-password-modal__submit:hover {
			background: #d4aa7a;
		}
	</style>

	<script>
		(function () {
			const header = document.querySelector('header[data-header-bg]');
			if (header) {
				const headerBg = header.getAttribute('data-header-bg');
				const headerStyle = header.getAttribute('data-header-style');
				if (headerBg) {
					header.style.backgroundImage = `url('${headerBg}')`;
				}
				if (headerStyle) {
					header.style.cssText += ';' + headerStyle;
				}
			}

			const menuBtn = document.getElementById('mobile-menu-btn');
			const mobileMenu = document.getElementById('mobile-menu');
			if (menuBtn && mobileMenu) {
				menuBtn.addEventListener('click', function () {
					mobileMenu.classList.toggle('open');
				});
			}

			const dropdownBtn = document.getElementById('user-dropdown-btn');
			const dropdownMenu = document.getElementById('user-dropdown-menu');
			const dropdownWrapper = document.getElementById('user-dropdown-wrapper');
			if (dropdownBtn && dropdownMenu) {
				dropdownBtn.addEventListener('click', function (e) {
					e.stopPropagation();
					dropdownMenu.classList.toggle('hidden');
				});
			}
			document.addEventListener('click', function (e) {
				if (dropdownWrapper && !dropdownWrapper.contains(e.target)) {
					dropdownMenu?.classList.add('hidden');
				}
			});

			window.updateCartBadge = function (count) {
				document.querySelectorAll('.cart-count-badge').forEach(el => {
					el.textContent = count;
					el.style.display = count > 0 ? 'flex' : 'none';
				});
			};

			fetch('/cart/count')
				.then(r => r.json())
				.then(d => window.updateCartBadge(d.cart_count || 0))
				.catch(() => { });

			const forceModal = document.getElementById('force-password-modal');
			if (forceModal) {
				forceModal.classList.add('is-open');
				document.body.style.overflow = 'hidden';
			}
		})();
	</script>
	@stack('scripts')
</body>
</html>