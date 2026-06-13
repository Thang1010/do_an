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
		<header class="relative bg-cover bg-center bg-no-repeat" data-header-bg="{{ $headerBg }}"
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
									style="width: 85px; height: 44px;"
									class="flex items-center justify-center gap-2 bg-transparent border-2 border-white text-white rounded-full hover:bg-white/20 transition">
									<img src="{{ Auth::user()->avatar_url }}" alt="Avatar"
										class="w-7 h-7 rounded-full object-cover">
									<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
										<path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
									</svg>
								</button>
								<div id="user-dropdown-menu"
									class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100">
									<a href="{{ route('customer.profile') }}"
										class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Hồ sơ cá
										nhân</a>
									<a href="{{ route('customer.profile.password.edit') }}"
										class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Đổi mật
										khẩu</a>
									<a href="{{ route('customer.orders') }}"
										class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Lịch sử
										đơn hàng</a>
									<a href="{{ route('customer.chat_history') }}"
										class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Lịch sử
										trò chuyện</a>
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
								class="bg-[#D9D9D9] text-[#8D5D5D] font-poppins font-semibold text-base py-2.5 px-6 rounded-full hover:bg-white transition shadow">Đăng
								nhập</a>
							<a href="{{ route('auth.register') }}"
								class="bg-[#30261C] text-white font-poppins font-semibold text-base py-2.5 px-6 rounded-full hover:bg-black transition shadow">Đăng
								ký</a>
						@endauth
					</div>

					<!-- Mobile hamburger -->
					<div class="lg:hidden flex items-center gap-3">
						<a href="{{ route('cart.index') }}" id="cart-btn-mobile" aria-label="Giỏ hàng"
							class="relative text-white">
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
							class="{{ request()->routeIs('home') ? 'text-white font-outfit font-semibold text-lg' : 'text-white/80 font-outfit font-medium text-lg hover:text-white' }} py-2 border-b border-white/10">Trang
							chủ</a>
						<a href="{{ route('menu.index') }}"
							class="{{ request()->routeIs('menu.*') ? 'text-white font-outfit font-semibold text-lg' : 'text-white/80 font-outfit font-medium text-lg hover:text-white' }} py-2 border-b border-white/10">Menu</a>
						<a href="{{ route('home.contact') }}"
							class="{{ request()->routeIs('home.contact') ? 'text-white font-outfit font-semibold text-lg' : 'text-white/80 font-outfit font-medium text-lg hover:text-white' }} py-2 border-b border-white/10">Liên
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
							<input type="text" name="search" value="{{ request('search') }}"
								placeholder="Tìm kiếm sản phẩm..."
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
		<div id="force-password-modal" class="force-password-modal" role="dialog" aria-modal="true"
			aria-label="Đặt mật khẩu XM COFFEE">
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
						<input id="force-password" type="password" name="password" autocomplete="new-password"
							placeholder="Nhập mật khẩu mới" required>
					</div>
					<div class="force-password-modal__field">
						<label for="force-password-confirm">Nhập lại mật khẩu</label>
						<input id="force-password-confirm" type="password" name="password_confirmation"
							autocomplete="new-password" placeholder="Nhập lại mật khẩu" required>
					</div>
					<div class="force-password-modal__hint">Mật khẩu tối thiểu 8 ký tự.</div>
					<button type="submit" class="force-password-modal__submit">Lưu mật khẩu</button>
				</form>
			</div>
		</div>
	@endif

	@auth
		@if(auth()->user()->isKhachHang() && session('show_voucher_popup'))
			@php
				$_uid = auth()->id();
				$_now = now();

				// Voucher đang hoạt động + còn trong thời hạn hôm nay
				$_todayVouchers = \App\Models\Voucher::where('trang_thai', 'đang hoạt động')
					->where(function ($q) use ($_now) {
						$q->whereNull('ngay_bat_dau')->orWhere('ngay_bat_dau', '<=', $_now);
					})
					->where(function ($q) use ($_now) {
						$q->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>=', $_now);
					})
					->get();

				// Lọc chỉ lấy voucher tài khoản này CHƯA nhận và còn số lượng
				// (1 tài khoản chỉ nhận được 1 voucher của 1 loại)
				$_claimableVouchers = $_todayVouchers->filter(function ($v) use ($_uid) {
					$claimed = \App\Models\VoucherNguoiDung::where('nguoi_dung_id', $_uid)
						->where('voucher_id', $v->id)
						->exists();
					if ($claimed)
						return false;
					if ((int) ($v->so_luong ?? 0) > 0) {
						$issued = \App\Models\VoucherNguoiDung::where('voucher_id', $v->id)->count();
						if ($issued >= (int) $v->so_luong)
							return false;
					}
					return true;
				});
			@endphp
			@if($_claimableVouchers->isNotEmpty())
				<div id="voucher-popup-modal" class="force-password-modal is-open" style="z-index: 10000;">
					<div class="force-password-modal__backdrop"
						onclick="document.getElementById('voucher-popup-modal').classList.remove('is-open')"></div>
					<div class="force-password-modal__panel" style="background: #2a1f18; padding: 32px 24px;">
						<button type="button" onclick="document.getElementById('voucher-popup-modal').classList.remove('is-open')"
							style="position: absolute; right: 16px; top: 12px; color: #fff; font-size: 20px; font-weight: bold; background: none; border: none; cursor: pointer;">×</button>
						<div class="force-password-modal__title" style="color: #F0DDB8; font-size: 26px;">🎁 Voucher Hôm Nay Của
							Bạn!</div>
						<p class="force-password-modal__desc" style="margin-bottom: 16px;">
							Có <strong style="color:#f0ddb8;">{{ $_claimableVouchers->count() }} voucher</strong> đang chờ bạn nhận
							hôm nay!
						</p>
						<div
							style="max-height: 280px; overflow-y: auto; margin-bottom: 16px; display: flex; flex-direction: column; gap: 10px; padding-right: 4px;">
							@foreach($_claimableVouchers as $v)
								<div
									style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); padding: 14px 16px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
									<div style="flex:1; min-width:0;">
										<div style="font-weight: 700; color: #f0ddb8; font-size: 15px;">{{ $v->ma_voucher }}</div>
										<div style="color: #e5e7eb; font-size: 13px; margin-top: 2px;">{{ $v->ten_voucher }}</div>
										<div style="opacity: 0.65; font-size: 12px; margin-top: 2px;">
											{{ $v->loai_giam === 'phần trăm' ? 'Giảm ' . rtrim(rtrim(number_format($v->gia_tri_giam, 2, ',', '.'), '0'), ',') . '%' : 'Giảm ' . number_format($v->gia_tri_giam, 0, ',', '.') . 'đ' }}
											@if($v->giam_toi_da) · Tối đa {{ number_format($v->giam_toi_da, 0, ',', '.') }}đ @endif
											@if($v->don_toi_thieu > 0) · Đơn từ {{ number_format($v->don_toi_thieu, 0, ',', '.') }}đ
											@endif
											@if($v->ngay_ket_thuc) · HSD:
											{{ \Carbon\Carbon::parse($v->ngay_ket_thuc)->format('d/m/Y') }} @endif
										</div>
									</div>
									<div style="flex-shrink:0;">
										<form action="{{ route('customer.vouchers.claim', $v->id) }}" method="POST" style="margin: 0;">
											@csrf
											<button type="submit"
												style="background: #059669; color: #fff; padding: 7px 18px; border-radius: 50px; font-weight: bold; font-size: 13px; border: none; cursor: pointer; white-space:nowrap;">Nhận</button>
										</form>
									</div>
								</div>
							@endforeach
						</div>
						<form action="{{ route('customer.vouchers.claim-all') }}" method="POST">
							@csrf
							<button type="submit"
								style="width:100%; background: #d97706; color:#fff; padding: 12px; border-radius: 50px; font-weight: bold; font-size: 15px; border: none; cursor: pointer; letter-spacing: 0.3px;">Nhận
								tất cả ({{ $_claimableVouchers->count() }} voucher)</button>
						</form>
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
		@php $footerStore = \App\Models\CuaHang::first(); @endphp
		<footer class="bg-[#30261C] text-[#F1F0EE] py-12 border-t border-[#8D5D5D]/30 relative z-10 w-full mt-auto">
			<div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 grid grid-cols-1 md:grid-cols-3 gap-12">
				{{-- Cột 1: Logo --}}
				<div class="flex items-center justify-center">
					<a href="{{ route('home') }}" class="inline-block">
						<img src="{{ asset('images/logo.png') }}" alt="XM Coffee Logo"
							class="h-21 w-auto object-contain brightness-0 invert">
					</a>
				</div>

				{{-- Cột 2: Liên kết nhanh --}}
				<div class="flex flex-col items-center text-center">
					<h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Liên kết nhanh</h3>
					<ul class="space-y-3 font-outfit text-sm opacity-80">
						<li><a href="{{ route('home') }}" class="hover:text-white transition">Trang chủ</a></li>
						<li><a href="{{ route('menu.index') }}" class="hover:text-white transition">Menu</a></li>
						<li><a href="{{ route('chatbot.index') }}" class="hover:text-white transition">Tư vấn</a></li>
					</ul>
				</div>

				{{-- Cột 3: Liên hệ (từ database) --}}
				<div class="flex flex-col items-center text-center">
					<h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Liên hệ</h3>
					<ul class="space-y-4 font-outfit text-sm opacity-80">
						@if($footerStore?->dia_chi)
							<li class="flex items-start gap-3">
								<svg class="w-5 h-5 text-[#8D5D5D] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
									stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
								</svg>
								<span>{{ $footerStore->dia_chi }}</span>
							</li>
						@endif
						@if($footerStore?->so_dien_thoai)
							<li class="flex items-center gap-3">
								<svg class="w-5 h-5 text-[#8D5D5D] shrink-0" fill="none" viewBox="0 0 24 24"
									stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
								</svg>
								<span>{{ $footerStore->so_dien_thoai }}</span>
							</li>
						@endif
						@if($footerStore?->lien_ket_trang)
							<li class="flex items-center gap-3">
								<svg class="w-5 h-5 text-[#8D5D5D] shrink-0" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M22 12c0-5.522-4.478-10-10-10S2 6.478 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12z" />
								</svg>
								<a href="{{ $footerStore->lien_ket_trang }}" target="_blank" rel="noopener noreferrer"
									class="hover:text-white transition">Facebook</a>
							</li>
						@endif
					</ul>
				</div>
			</div>

			<div
				class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 mt-12 pt-8 border-t border-white/10 text-center opacity-60 font-outfit text-sm">
				&copy; {{ date('Y') }} XM Coffee. All rights reserved.
			</div>
		</footer>
	@endif

	<!-- Global Size Selection Modal -->
	<div id="global-size-modal"
		style="position: fixed; inset: 0; display: none; align-items: center; justify-content: center; z-index: 10000; padding: 20px;">
		<div style="position: absolute; inset: 0; background: rgba(18, 12, 8, 0.75); backdrop-filter: blur(2px);"
			onclick="closeGlobalSizeModal()"></div>
		<div
			style="position: relative; width: min(460px, 92vw); background: #1f1710; border: 1px solid rgba(141, 93, 93, 0.5); border-radius: 18px; padding: 28px 26px; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45); color: #f1f0ee; font-family: 'Outfit', sans-serif;">
			<button type="button" onclick="closeGlobalSizeModal()"
				style="position: absolute; right: 16px; top: 12px; background: none; border: none; color: #f1f0ee; font-size: 24px; cursor: pointer;">&times;</button>
			<div id="global-size-modal-title"
				style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 8px; color: #F0DDB8;">
				Tên sản phẩm</div>
			<p id="global-size-modal-subtitle"
				style="font-size: 14px; color: rgba(241, 240, 238, 0.72); text-align: center; margin-bottom: 20px;">Vui
				lòng chọn kích cỡ</p>

			<div id="global-size-modal-options"
				style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-bottom: 24px;">
				<!-- Sizes will be injected here -->
			</div>

			<div id="global-temp-section" style="display: none; text-align: center; margin-bottom: 24px;">
				<p style="font-size: 14px; color: rgba(241, 240, 238, 0.72); margin-bottom: 12px;">Vui lòng chọn nhiệt
					độ</p>
				<div style="display: flex; justify-content: center; gap: 12px;">
					<button type="button" class="global-modal-temp-btn" data-temp="nóng"
						onclick="selectGlobalModalTemp(this)"
						style="padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #f1f0ee; cursor: pointer; transition: all 0.2s; min-width: 80px;">Nóng</button>
					<button type="button" class="global-modal-temp-btn" data-temp="lạnh"
						onclick="selectGlobalModalTemp(this)"
						style="padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #f1f0ee; cursor: pointer; transition: all 0.2s; min-width: 80px;">Lạnh</button>
				</div>
			</div>

			<div id="global-qty-section" style="text-align: center; margin-bottom: 24px;">
				<p style="font-size: 14px; color: rgba(241, 240, 238, 0.72); margin-bottom: 12px;">Số lượng</p>
				<div style="display: flex; justify-content: center; align-items: center; gap: 16px;">
					<button type="button" onclick="changeGlobalQty(-1)"
						style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #f1f0ee; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center;">-</button>
					<span id="global-modal-qty"
						style="font-size: 18px; font-weight: 600; min-width: 24px; text-align: center;">1</span>
					<button type="button" onclick="changeGlobalQty(1)"
						style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #f1f0ee; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center;">+</button>
				</div>
			</div>

			<div style="margin-bottom: 24px;">
				<p style="font-size: 14px; color: rgba(241, 240, 238, 0.72); margin-bottom: 8px; text-align: center;">
					Ghi chú thêm (Không bắt buộc)</p>
				<input type="text" id="global-modal-note" placeholder="Ví dụ: Ít đá, không đường..."
					style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); color: #f1f0ee; font-family: inherit; font-size: 14px; outline: none; transition: border-color 0.2s;"
					onfocus="this.style.borderColor='#c49a6c'" onblur="this.style.borderColor='rgba(255,255,255,0.12)'">
			</div>

			<button type="button" id="global-modal-submit-btn" onclick="confirmGlobalSizeAndAdd()"
				style="width: 100%; padding: 12px 16px; border-radius: 999px; border: none; background: #c49a6c; color: #1a120c; font-weight: 600; letter-spacing: 0.02em; cursor: pointer; transition: background 0.3s;">Thêm
				vào giỏ</button>
		</div>
	</div>

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

		// Global Size Modal Logic
		let globalProductData = null;

		function closeGlobalSizeModal() {
			document.getElementById('global-size-modal').style.display = 'none';
		}

		function selectGlobalModalSize(btn) {
			document.querySelectorAll('.global-modal-size-btn').forEach(b => {
				b.style.background = 'rgba(255,255,255,0.08)';
				b.style.borderColor = 'rgba(255,255,255,0.12)';
				b.style.color = '#f1f0ee';
				b.classList.remove('active');
			});
			btn.style.background = 'rgba(196, 154, 108, 0.2)';
			btn.style.borderColor = '#c49a6c';
			btn.style.color = '#F0DDB8';
			btn.classList.add('active');
			globalProductData.selectedSizeId = btn.dataset.sizeId;
		}

		function selectGlobalModalTemp(btn) {
			document.querySelectorAll('.global-modal-temp-btn').forEach(b => {
				b.style.background = 'rgba(255,255,255,0.08)';
				b.style.borderColor = 'rgba(255,255,255,0.12)';
				b.style.color = '#f1f0ee';
				b.classList.remove('active');
			});
			btn.style.background = 'rgba(196, 154, 108, 0.2)';
			btn.style.borderColor = '#c49a6c';
			btn.style.color = '#F0DDB8';
			btn.classList.add('active');
			globalProductData.selectedTemp = btn.dataset.temp;
		}

		function changeGlobalQty(delta) {
			if (!globalProductData) return;
			let qty = globalProductData.qty + delta;
			if (qty < 1) qty = 1;
			globalProductData.qty = qty;
			document.getElementById('global-modal-qty').textContent = qty;
		}

		function confirmGlobalSizeAndAdd() {
			if (!globalProductData) return;
			const sizeId = globalProductData.selectedSizeId;
			if (globalProductData.sizes.length > 0 && !sizeId) {
				alert('Vui lòng chọn kích cỡ!');
				return;
			}
			if (globalProductData.showTemp && !globalProductData.selectedTemp) {
				alert('Vui lòng chọn nhiệt độ!');
				return;
			}

			const noteInput = document.getElementById('global-modal-note');
			const noteText = noteInput ? noteInput.value.trim() : '';

			closeGlobalSizeModal();
			if (typeof window.launchCartAnimation === 'function') {
				window.launchCartAnimation(globalProductData.imgEl, globalProductData.imgSrc);
			}

			const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
			const bodyData = { product_id: globalProductData.productId, qty: globalProductData.qty };
			if (sizeId) {
				bodyData.size_id = sizeId;
			}
			if (globalProductData.showTemp && globalProductData.selectedTemp) {
				const tempStr = globalProductData.selectedTemp === 'nóng' ? 'Nóng' : 'Lạnh';
				bodyData.nhiet_do = tempStr;
			}
			if (noteText) {
				bodyData.ghi_chu = noteText;
			}

			fetch(globalProductData.addUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
				body: JSON.stringify(bodyData),
			})
				.then(r => r.json())
				.then(d => { if (d.success && window.updateCartBadge) window.updateCartBadge(d.cart_count || 0); })
				.catch(err => console.error('Cart error:', err));
		}

		window.showGlobalSizeModal = function (productId, productName, imgSrc, addUrl, sizes, imgEl, nhietDo, suggestedNote) {
			globalProductData = {
				productId, imgSrc, addUrl, imgEl, sizes,
				selectedSizeId: sizes.length > 0 ? sizes[0].id : null,
				showTemp: !!nhietDo,
				selectedTemp: null,
				qty: 1
			};
			document.getElementById('global-modal-qty').textContent = 1;

			const noteInput = document.getElementById('global-modal-note');
			if (noteInput) {
				noteInput.value = suggestedNote || '';
			}

			const sizeOptionsContainer = document.getElementById('global-size-modal-options');
			const tempSection = document.getElementById('global-temp-section');
			const subtitle = document.getElementById('global-size-modal-subtitle');

			document.getElementById('global-size-modal-title').textContent = productName;

			if (globalProductData.showTemp) {
				tempSection.style.display = 'block';
				const temps = nhietDo.split(',').map(t => t.trim().toLowerCase());
				let defaultTempBtn = null;
				document.querySelectorAll('.global-modal-temp-btn').forEach(b => {
					if (temps.includes(b.dataset.temp)) {
						b.style.display = 'inline-block';
						if (b.dataset.temp === 'lạnh') {
							defaultTempBtn = b;
						}
					} else {
						b.style.display = 'none';
					}
					// Reset styles
					b.style.background = 'rgba(255,255,255,0.08)';
					b.style.borderColor = 'rgba(255,255,255,0.12)';
					b.style.color = '#f1f0ee';
					b.classList.remove('active');
				});

				if (defaultTempBtn) {
					selectGlobalModalTemp(defaultTempBtn);
				} else if (temps.length === 1) {
					document.querySelectorAll('.global-modal-temp-btn').forEach(b => {
						if (b.dataset.temp === temps[0]) selectGlobalModalTemp(b);
					});
				}
			} else {
				tempSection.style.display = 'none';
			}

			if (sizes.length > 0) {
				subtitle.style.display = 'block';
				let html = '';
				sizes.forEach((s, index) => {
					const isActive = index === 0;
					const bg = isActive ? 'rgba(196, 154, 108, 0.2)' : 'rgba(255,255,255,0.08)';
					const border = isActive ? '#c49a6c' : 'rgba(255,255,255,0.12)';
					const color = isActive ? '#F0DDB8' : '#f1f0ee';
					const cls = isActive ? 'global-modal-size-btn active' : 'global-modal-size-btn';

					const sizeDisplayName = s.code ? `${s.code} (${s.name})` : s.name;
					html += `<button type="button" class="${cls}" data-size-id="${s.id}" onclick="selectGlobalModalSize(this)" style="padding: 10px 16px; border-radius: 8px; border: 1px solid ${border}; background: ${bg}; color: ${color}; cursor: pointer; transition: all 0.2s; min-width: 80px; display: flex; flex-direction: column; align-items: center; gap: 4px;">
						<span style="font-weight: 600;">${sizeDisplayName}</span>
					</button>`;
				});
				sizeOptionsContainer.innerHTML = html;
				document.getElementById('global-size-modal').style.display = 'flex';
			} else {
				sizeOptionsContainer.innerHTML = '';
				subtitle.style.display = 'none';
				document.getElementById('global-size-modal').style.display = 'flex';
			}
		};
	</script>
	@stack('scripts')
</body>

</html>