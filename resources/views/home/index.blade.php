<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>XM Coffee - Trang chủ</title>
	<meta name="description" content="XM Coffee - Nạp năng lượng, thưởng thức vị ngon. Đặt món cà phê, trà, bánh và đồ ăn vặt ngay hôm nay.">
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>
<body>

	<!-- ============ HEADER & HERO SECTION ============ -->
	<header class="relative bg-cover bg-center bg-no-repeat"
		style="background-image: url('https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/lo9ik0uh_expires_30_days.png'); min-height: 100vh; max-height: 1000px;">
		<div class="absolute inset-0 bg-black/20"></div>

		<div class="relative z-10 max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16">
			<!-- ===== Navbar ===== -->
			<div class="flex items-center justify-between py-6">
				<!-- Logo -->
				<a href="{{ route('home') }}" class="flex items-center" aria-label="XM Coffee - Trang chủ">
					<div class="relative flex items-center">
						<img src="{{ asset('images/logo.png') }}" class="w-[143px] md:w-[207px] h-auto object-contain z-0" alt="XM Coffee Logo"/>
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
						<input id="search-input" type="text" name="search" placeholder="Tìm kiếm..."
							class="bg-[#D9D9D9] text-gray-800 text-sm py-3 px-5 pr-12 rounded-full w-60 focus:outline-none focus:ring-2 focus:ring-[#8D5D5D] font-outfit">
						<button type="submit" class="absolute right-4 top-3" aria-label="Tìm kiếm">
							<svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
							</svg>
						</button>
					</form>
					<!-- Cart icon -->
					<a href="{{ route('cart.index') }}" id="cart-btn" aria-label="Giỏ hàng" class="relative text-white hover:text-[#F0EADC] transition">
						<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
						</svg>
					</a>
					@auth
						<!-- User dropdown -->
						<div class="relative" id="user-dropdown-wrapper">
							<button id="user-dropdown-btn" class="flex items-center gap-2 bg-[#D9D9D9] text-[#30261C] font-poppins font-semibold text-sm py-2.5 px-5 rounded-full hover:bg-white transition shadow">
								<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
								</svg>
								{{ Auth::user()->name }}
							</button>
							<div id="user-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100">
								<a href="{{ route('customer.profile') }}" class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Hồ sơ cá nhân</a>
								<a href="{{ route('customer.orders') }}" class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Lịch sử đơn hàng</a>
								<a href="{{ route('customer.points') }}" class="block px-4 py-2 text-sm text-[#30261C] hover:bg-[#F1F0EE] font-outfit">Điểm thưởng</a>
								<hr class="my-1 border-[#E2D9C8]">
								<form method="POST" action="{{ route('auth.logout') }}">
									@csrf
									<button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-outfit">Đăng xuất</button>
								</form>
							</div>
						</div>
					@else
						<!-- Login / Register -->
						<a href="{{ route('auth.login') }}" id="login-btn" class="bg-[#D9D9D9] text-[#8D5D5D] font-poppins font-semibold text-base py-2.5 px-6 rounded-full hover:bg-white transition shadow">Đăng nhập</a>
						<a href="{{ route('auth.register') }}" class="bg-[#30261C] text-white font-poppins font-semibold text-base py-2.5 px-6 rounded-full hover:bg-black transition shadow">Đăng ký</a>
					@endauth
				</div>

				<!-- Mobile hamburger -->
				<div class="lg:hidden flex items-center gap-3">
					<a href="{{ route('cart.index') }}" id="cart-btn-mobile" aria-label="Giỏ hàng" class="text-white">
						<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
						</svg>
					</a>
					<button id="mobile-menu-btn" aria-label="Menu" class="text-white hover:text-gray-200 focus:outline-none">
						<svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
						</svg>
					</button>
				</div>
			</div>

			<!-- Mobile Menu Dropdown -->
			<div id="mobile-menu">
				<nav class="flex flex-col gap-4">
					<a href="{{ route('home') }}" class="text-white font-outfit font-medium text-lg py-2 border-b border-white/10">Trang chủ</a>
					<a href="{{ route('menu.index') }}" class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Menu</a>
					<a href="{{ route('home.about') }}" class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Về chúng tôi</a>
					<a href="{{ route('home.contact') }}" class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Liên hệ</a>
					@auth
						<a href="{{ route('customer.profile') }}" class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Hồ sơ</a>
						<form method="POST" action="{{ route('auth.logout') }}">
							@csrf
							<button type="submit" class="text-red-400 font-outfit font-medium text-lg py-2 w-full text-left">Đăng xuất</button>
						</form>
					@else
						<a href="{{ route('auth.login') }}" class="text-white/80 font-outfit font-medium text-lg py-2 border-b border-white/10 hover:text-white">Đăng nhập</a>
						<a href="{{ route('auth.register') }}" class="text-white/80 font-outfit font-medium text-lg py-2 hover:text-white">Đăng ký</a>
					@endauth
					<!-- Mobile Search -->
					<form action="{{ route('menu.index') }}" method="GET" class="mt-2 relative">
						<input type="text" name="search" placeholder="Tìm kiếm sản phẩm..."
							class="w-full bg-white/10 text-white placeholder-white/60 text-base py-3 px-5 pr-12 rounded-full focus:outline-none focus:ring-2 focus:ring-white/40 font-outfit">
						<button type="submit" class="absolute right-4 top-3.5" aria-label="Tìm kiếm">
							<svg class="w-5 h-5 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
							</svg>
						</button>
					</form>
				</nav>
			</div>

			<!-- ===== Hero Content ===== -->
			<div class="mt-20 md:mt-[120px] max-w-[557px]">
				<h2 class="hero-title-xin mb-4">Xin chào</h2>
				<h1 class="hero-title-main mb-6">Nạp năng lượng,<br>thưởng thức vị ngon.</h1>
				<p class="hero-desc mb-10 max-w-[431px] opacity-95">
					Dù là một bữa sáng nhanh gọn, một buổi trà chiều thư giãn hay một miếng bánh ngọt ngào cho ngày thêm vui, XM Coffee luôn sẵn sàng đồng hành cùng bạn.
				</p>
				<a href="{{ route('menu.index') }}" id="hero-order-btn"
					class="inline-block bg-white text-[#302617] font-outfit font-medium text-lg py-[16px] px-10 rounded-full hover:bg-gray-100 transition shadow-xl hover:scale-105 transform duration-300">
					Gọi món
				</a>
			</div>
		</div>
	</header>

	<!-- ============ CATEGORIES SECTION ============ -->
	<section class="bg-[#E2D9C8] py-8">
		<div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16">
			<div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 lg:gap-28">
				@if(isset($categories) && $categories->count() > 0)
					@foreach($categories as $category)
						@php
							$catSlug = $categorySlugs[$category->id] ?? Str::slug($category->ten_danh_muc);
							$catImage = $categoryImages[$category->id] ?? asset('images/ca_phe_nau_da.jpg');
						@endphp
						<a href="{{ route('menu.index', ['category' => $catSlug]) }}"
							class="flex flex-col items-center gap-3 cursor-pointer hover:-translate-y-1 transition duration-300 group">
							<img src="{{ $catImage }}"
								class="w-[71px] h-[71px] object-cover rounded-full drop-shadow-md group-hover:drop-shadow-lg"
								alt="{{ $category->ten_danh_muc }}"/>
							<span class="cat-label">{{ $category->ten_danh_muc }}</span>
						</a>
					@endforeach
				@else
					<p class="text-[#30261C]/70 text-sm font-outfit">Chưa có danh mục hiển thị.</p>
				@endif
			</div>
		</div>
	</section>

	<main class="py-16">

		<!-- ============ BEST SELLERS: DRINKS ============ -->
		<section id="best-drinks" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-20 mb-20 relative">
			<div class="text-center mb-12">
				<h2 class="section-title">Đồ bán chạy trong tuần</h2>
			</div>

			<div class="relative">
				<!-- Left Arrow -->
				<button id="drinks-left-arrow" aria-label="Trước" class="arrow-btn hidden lg:flex" style="left: -36px;" onclick="slideCarousel('drinks', -1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
					</svg>
				</button>

				<!-- Product Grid -->
				<div id="drinks-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
					@if(isset($bestDrinks) && $bestDrinks->count() > 0)
						@foreach($bestDrinks as $product)
						<div id="product-{{ Str::slug($product->ten_san_pham) }}" class="product-card">
							<div class="relative">
								<img src="{{ $product->image_url }}"
									class="card-img" alt="{{ $product->ten_san_pham }}"/>
								<button class="heart-btn" aria-label="Yêu thích" onclick="toggleWishlist({{ $product->id }}, this)">
									<svg class="w-7 h-7 text-[#F1F0EE]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
									</svg>
								</button>
							</div>
							<div class="flex flex-col gap-2">
								<h3 class="product-name">{{ $product->ten_san_pham }}</h3>
								<p class="product-desc">{{ Str::limit($product->mo_ta, 80) }}</p>
							</div>
							<div class="flex items-center justify-between mt-auto pt-2">
								<span class="product-price">{{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ</span>
								<a href="{{ route('menu.show', $product->id) }}" class="product-btn">Gọi món</a>
							</div>
						</div>
						@endforeach
					@else
						<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có sản phẩm bán chạy.</div>
					@endif
				</div>

				<!-- Right Arrow -->
				<button id="drinks-right-arrow" aria-label="Tiếp theo" class="arrow-btn hidden lg:flex" style="right: -36px;" onclick="slideCarousel('drinks', 1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
					</svg>
				</button>
			</div>
		</section>

		<!-- ============ BEST SELLERS: DESSERTS ============ -->
		<section id="best-desserts" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-20 mb-20 relative">
			<div class="text-center mb-12">
				<h2 class="section-title">Đồ tráng miệng bán chạy trong tuần</h2>
				<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/jdl1gn07_expires_30_days.png"
					class="w-12 h-auto mx-auto mt-4" alt="Decoration"/>
			</div>

			<div class="relative">
				<!-- Left Arrow -->
				<button id="desserts-left-arrow" aria-label="Trước" class="arrow-btn hidden lg:flex" style="left: -36px;" onclick="slideCarousel('desserts', -1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
					</svg>
				</button>

				<!-- Product Grid -->
				<div id="desserts-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
					@if(isset($bestDesserts) && $bestDesserts->count() > 0)
						@foreach($bestDesserts as $product)
						<div id="product-{{ Str::slug($product->ten_san_pham) }}" class="product-card">
							<div class="relative">
								<img src="{{ $product->image_url }}"
									class="card-img" alt="{{ $product->ten_san_pham }}"/>
								<button class="heart-btn" aria-label="Yêu thích" onclick="toggleWishlist({{ $product->id }}, this)">
									<svg class="w-7 h-7 text-[#F1F0EE]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
									</svg>
								</button>
							</div>
							<div class="flex flex-col gap-2">
								<h3 class="product-name">{{ $product->ten_san_pham }}</h3>
								<p class="product-desc">{{ Str::limit($product->mo_ta, 80) }}</p>
							</div>
							<div class="flex items-center justify-between mt-auto pt-2">
								<span class="product-price">{{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ</span>
								<a href="{{ route('menu.show', $product->id) }}" class="product-btn">Gọi món</a>
							</div>
						</div>
						@endforeach
					@else
						<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có đồ tráng miệng bán chạy.</div>
					@endif
				</div>

				<!-- Right Arrow -->
				<button id="desserts-right-arrow" aria-label="Tiếp theo" class="arrow-btn hidden lg:flex" style="right: -36px;" onclick="slideCarousel('desserts', 1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
					</svg>
				</button>
			</div>
		</section>

		<!-- ============ DISCOVER BANNER ============ -->
		<section id="discover-banner" class="bg-[#E2D9C8] mb-20 overflow-hidden">
			<div class="max-w-[1680px] mx-auto flex flex-col md:flex-row items-center justify-between">
				<!-- Left Image -->
				<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/wqh9obqo_expires_30_days.png"
					class="w-full md:w-[33%] object-cover self-stretch hidden lg:block" alt="Coffee Banner Left"/>

				<!-- Center Content -->
				<div class="flex-1 flex flex-col items-center text-center px-8 py-16 md:py-12">
					<h2 class="discover-title max-w-xl mb-10">
						Hãy xem qua những hương vị ngon nhất của chúng tôi!
					</h2>
					<a href="{{ route('menu.index') }}" id="discover-btn"
						class="bg-[#30261C] text-[#F1F0EE] inline-flex items-center gap-4 py-4 px-10 rounded-full hover:bg-black hover:scale-105 transition shadow-lg font-outfit font-medium text-lg">
						<span>Khám phá sản phẩm</span>
						<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/oxwy1fzq_expires_30_days.png"
							class="w-8 h-6 object-cover rounded-full" alt="Arrow">
					</a>
				</div>

				<!-- Right Image -->
				<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/pg2pznoc_expires_30_days.png"
					class="w-full md:w-[33%] object-cover self-stretch hidden lg:block" alt="Coffee Banner Right"/>
			</div>
		</section>

		<!-- ============ TESTIMONIALS ============ -->
		<section id="testimonials" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 mb-20">
			<div class="text-center mb-12">
				<p class="testimonial-subtitle mb-2">Hãy đến và tham gia nào</p>
				<h2 class="testimonial-title">Khách hàng yêu quý của chúng tôi</h2>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
				@if(isset($testimonials) && $testimonials->count() > 0)
					@foreach($testimonials as $review)
					<div id="testimonial-{{ $review->id }}" class="bg-[#E2D9C8]/40 border border-[#30261C]/10 rounded-[20px] p-8 flex flex-col {{ $loop->index === 1 ? 'border-2 border-dashed border-[#30261C]/20 transform hover:-translate-y-2 transition duration-300 shadow-md' : '' }}">
						<div class="flex items-start justify-between mb-6">
							<div class="flex items-center gap-4">
								<img src="{{ optional($review->nguoiDung)->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(optional($review->nguoiDung)->ho_ten ?? 'Khach hang').'&background=E2D9C8&color=30261C' }}"
									class="w-14 h-14 rounded-full object-cover shadow-sm" alt="{{ optional($review->nguoiDung)->ho_ten ?? 'Khách hàng' }} Avatar"/>
								<div>
									<h4 class="text-[#30261C] text-lg font-bold font-outfit">{{ optional($review->nguoiDung)->ho_ten ?? 'Khách hàng' }}</h4>
									<p class="text-[#30261C]/80 text-sm font-medium font-poppins">{{ optional($review->sanPham)->ten_san_pham ?? '—' }}</p>
								</div>
							</div>
							<!-- Stars -->
							<div class="flex items-center gap-0.5">
								@for($i = 0; $i < 5; $i++)
									<svg class="w-5 h-5 {{ $i < ($review->so_sao ?? 0) ? 'text-yellow-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
										<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
									</svg>
								@endfor
							</div>
						</div>
						<p class="text-[#30261C] text-base leading-relaxed flex-1 font-outfit">{{ $review->noi_dung ?? '' }}</p>
					</div>
					@endforeach
				@else
					<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có đánh giá từ khách hàng.</div>
				@endif
			</div>

			<!-- Dot indicators -->
			<div class="dot-row mt-8">
				<div class="dot active"></div>
				<div class="dot"></div>
				<div class="dot"></div>
			</div>
		</section>

		<!-- ============ NEWSLETTER SECTION ============ -->
		<section id="newsletter" class="bg-[#E2D9C8] overflow-hidden">
			<div class="max-w-[1680px] mx-auto flex flex-col md:flex-row items-center justify-between">
				<!-- Left Image -->
				<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/39vpn9o0_expires_30_days.png"
					class="w-full md:w-[33%] h-80 object-cover hidden lg:block" alt="Newsletter Left"/>

				<!-- Content -->
				<div class="flex-1 flex flex-col justify-center px-8 md:px-16 py-16 md:py-0 w-full max-w-2xl mx-auto">
					<h2 class="newsletter-title mb-4">
						Tham gia ngay và nhận ngay ưu đãi giảm giá 15%!
					</h2>
					<p class="text-[#30261C] text-lg mb-8 opacity-90 font-outfit">
						Đăng ký nhận bản tin của chúng tôi để nhận mã giảm giá 15%.
					</p>
					<form id="newsletter-form" class="flex flex-col sm:flex-row items-center gap-4 w-full"
						action="{{ route('newsletter.subscribe') }}" method="POST"
						onsubmit="handleNewsletter(event)">
						@csrf
						<div class="flex-1 flex items-center bg-[#F1F0EE] rounded-full px-6 py-1 h-16 w-full shadow-inner">
							<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/h1vz55eo_expires_30_days.png"
								class="w-6 h-6 mr-3 opacity-60" alt="Email Icon"/>
							<input id="newsletter-email" type="email" name="email" placeholder="Địa chỉ Email" required
								class="bg-transparent text-[#30261C] text-lg w-full outline-none placeholder-gray-500 font-outfit"/>
						</div>
						<button id="newsletter-submit" type="submit"
							class="bg-[#30261C] text-[#F1F0EE] text-lg font-bold font-outfit h-16 px-10 rounded-full hover:bg-black transition whitespace-nowrap w-full sm:w-auto shadow-md">
							Đặt mua
						</button>
					</form>
				</div>

				<!-- Right Image -->
				<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/52bgwn8o_expires_30_days.png"
					class="w-full md:w-[33%] h-80 object-cover hidden lg:block" alt="Newsletter Right"/>
			</div>
		</section>

	</main>

	<!-- ============ FOOTER ============ -->
	<footer class="bg-[#30261C] pt-20 pb-12 w-full">
		<div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16">
			<div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-12">
				<!-- PRIVACY -->
				<div class="flex flex-col">
					<h5 class="text-white text-xl font-bold mb-6 font-outfit tracking-wide">PRIVACY</h5>
					<ul class="flex flex-col gap-4 text-white/70 font-outfit">
						<li><a href="#" class="hover:text-white transition">Terms of use</a></li>
						<li><a href="#" class="hover:text-white transition">Privacy policy</a></li>
						<li><a href="#" class="hover:text-white transition">Cookies</a></li>
					</ul>
				</div>
				<!-- SERVICES -->
				<div class="flex flex-col">
					<h5 class="text-white text-xl font-bold mb-6 font-outfit tracking-wide">SERVICES</h5>
					<ul class="flex flex-col gap-4 text-white/70 font-outfit">
						<li><a href="{{ route('menu.index') }}" class="hover:text-white transition">Menu</a></li>
						<li><a href="{{ route('cart.index') }}" class="hover:text-white transition">Đặt trước</a></li>
						<li><a href="{{ route('chatbot.index') }}" class="hover:text-white transition">Tư vấn</a></li>
					</ul>
				</div>
				<!-- ABOUT US -->
				<div class="flex flex-col">
					<h5 class="text-white text-xl font-bold mb-6 font-outfit tracking-wide">ABOUT US</h5>
					<ul class="flex flex-col gap-4 text-white/70 font-outfit">
						<li><a href="{{ route('home.about') }}" class="hover:text-white transition">Về chúng tôi</a></li>
						<li><a href="{{ route('home.contact') }}" class="hover:text-white transition">Liên hệ</a></li>
						<li><a href="#" class="hover:text-white transition">Câu chuyện của chúng tôi</a></li>
					</ul>
				</div>
				<!-- INFORMATION -->
				<div class="flex flex-col">
					<h5 class="text-white text-xl font-bold mb-6 font-outfit tracking-wide">INFORMATION</h5>
					<ul class="flex flex-col gap-4 text-white/70 font-outfit">
						<li><a href="{{ route('auth.register') }}" class="hover:text-white transition">Đăng ký thành viên</a></li>
						<li><a href="{{ route('customer.points') }}" class="hover:text-white transition">Điểm thưởng</a></li>
						<li><a href="#" class="hover:text-white transition">Tuyển dụng</a></li>
					</ul>
				</div>
				<!-- SOCIAL MEDIA -->
				<div class="flex flex-col col-span-2 md:col-span-1 border-t border-white/10 pt-8 md:pt-0 md:border-none">
					<h5 class="text-white text-xl font-bold mb-6 font-outfit tracking-wide">SOCIAL MEDIA</h5>
					<div class="flex items-center gap-4">
						<a href="#" aria-label="Facebook" class="hover:-translate-y-1 transition bg-white/10 p-2.5 rounded-full">
							<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/4tj2se9h_expires_30_days.png"
								class="w-6 h-6 object-contain filter invert" alt="Facebook"/>
						</a>
						<a href="#" aria-label="Instagram" class="hover:-translate-y-1 transition bg-white/10 p-2.5 rounded-full">
							<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/4mgp6fix_expires_30_days.png"
								class="w-6 h-6 object-contain filter invert" alt="Instagram"/>
						</a>
						<a href="#" aria-label="Twitter" class="hover:-translate-y-1 transition bg-white/10 p-2.5 rounded-full">
							<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/5lsxlnsd_expires_30_days.png"
								class="w-6 h-6 object-contain filter invert" alt="Twitter"/>
						</a>
						<a href="#" aria-label="YouTube" class="hover:-translate-y-1 transition bg-white/10 p-2.5 rounded-full">
							<img src="https://storage.googleapis.com/tagjs-prod.appspot.com/v1/JNUmkMcFVh/t60rk7au_expires_30_days.png"
								class="w-6 h-6 object-contain filter invert" alt="YouTube"/>
						</a>
					</div>
				</div>
			</div>

			<div class="border-t border-white/10 pt-8 text-center">
				<p class="text-white/50 text-sm font-outfit">© {{ date('Y') }} XM Coffee. All rights reserved.</p>
			</div>
		</div>
	</footer>

	<!-- ============ FLOATING BUTTONS ============ -->
	<!-- Chatbot Button -->
	<button id="chatbot-btn" aria-label="Chatbot tư vấn" onclick="window.location.href='{{ route('chatbot.index') }}'">
		<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
			<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
		</svg>
	</button>

	<!-- QR Quick Order Button -->
	<button id="qr-btn" aria-label="Quét QR gọi món" onclick="openQRModal()">
		<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
		</svg>
	</button>

	<!-- ============ QR MODAL ============ -->
	<div id="qr-modal" class="fixed inset-0 z-[1000] flex items-center justify-center hidden">
		<div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeQRModal()"></div>
		<div class="relative bg-[#F1F0EE] rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
			<button onclick="closeQRModal()" class="absolute top-4 right-4 text-[#30261C]/50 hover:text-[#30261C]">
				<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
				</svg>
			</button>
			<h3 class="font-playfair text-2xl font-bold text-[#30261C] mb-2">Gọi món tại bàn</h3>
			<p class="text-[#30261C]/70 font-outfit mb-6">Quét mã QR để gọi món nhanh tại bàn của bạn</p>
			<div class="bg-white p-4 rounded-xl inline-block shadow-md mb-4">
				<img src="{{ asset('images/qr-table.png') }}" alt="QR Code" class="w-48 h-48 object-contain"
					onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(url('/order/qr')) }}'"/>
			</div>
			<p class="text-[#30261C]/50 text-sm font-outfit">Hoặc nhập số bàn của bạn bên dưới</p>
			<div class="mt-4 flex gap-2">
				<input id="table-number-input" type="number" min="1" max="99" placeholder="Số bàn..."
					class="flex-1 bg-white border border-[#E2D9C8] rounded-xl px-4 py-3 text-[#30261C] font-outfit text-center focus:outline-none focus:ring-2 focus:ring-[#8D5D5D]"/>
				<button onclick="goToTableOrder()" class="bg-[#30261C] text-white font-outfit font-medium px-6 py-3 rounded-xl hover:bg-black transition">
					Tiếp tục
				</button>
			</div>
		</div>
	</div>

	<!-- ============ USER DROPDOWN SCRIPT ============ -->
	<script>
		// Mobile menu toggle
		document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
			const menu = document.getElementById('mobile-menu');
			menu.classList.toggle('open');
		});

		// User dropdown
		document.getElementById('user-dropdown-btn')?.addEventListener('click', function() {
			const menu = document.getElementById('user-dropdown-menu');
			menu.classList.toggle('hidden');
		});

		// Close dropdown when clicking outside
		document.addEventListener('click', function(e) {
			const wrapper = document.getElementById('user-dropdown-wrapper');
			if (wrapper && !wrapper.contains(e.target)) {
				document.getElementById('user-dropdown-menu')?.classList.add('hidden');
			}
		});

		// Wishlist / heart toggle
		function toggleWishlist(productId, btn) {
			const svg = btn.querySelector('svg');
			const isActive = btn.dataset.active === 'true';
			if (isActive) {
				svg.setAttribute('fill', 'none');
				btn.dataset.active = 'false';
			} else {
				svg.setAttribute('fill', '#F1F0EE');
				btn.dataset.active = 'true';
			}
			// Optionally: send AJAX request to toggle wishlist
		}

		// Newsletter form
		function handleNewsletter(e) {
			// Allow the form to submit normally via POST
			// Or intercept for AJAX:
			// e.preventDefault();
		}

		// QR Modal
		function openQRModal() {
			document.getElementById('qr-modal').classList.remove('hidden');
		}
		function closeQRModal() {
			document.getElementById('qr-modal').classList.add('hidden');
		}
		function goToTableOrder() {
			const tableNum = document.getElementById('table-number-input').value;
			if (tableNum && tableNum > 0) {
				window.location.href = '{{ url("/order/table") }}/' + tableNum;
			} else {
				alert('Vui lòng nhập số bàn hợp lệ.');
			}
		}

		// Carousel arrows (visual feedback only - no actual scroll since grid layout)
		function slideCarousel(section, direction) {
			// Future: implement actual carousel sliding
			// For now, show a subtle animation on the grid
			const grid = document.getElementById(section + '-grid');
			if (grid) {
				grid.style.opacity = '0.7';
				grid.style.transform = direction > 0 ? 'translateX(-10px)' : 'translateX(10px)';
				setTimeout(() => {
					grid.style.opacity = '1';
					grid.style.transform = 'translateX(0)';
					grid.style.transition = 'all 0.3s ease';
				}, 150);
			}
		}

		// Smooth scroll for hero
		document.querySelectorAll('a[href^="#"]').forEach(anchor => {
			anchor.addEventListener('click', function (e) {
				const target = document.querySelector(this.getAttribute('href'));
				if (target) {
					e.preventDefault();
					target.scrollIntoView({ behavior: 'smooth' });
				}
			});
		});
	</script>

</body>
</html>
