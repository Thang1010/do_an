@extends('customer.layout.app')

@section('title', 'XM Coffee - Trang chủ')
@section('meta_description', 'XM Coffee - Nạp năng lượng, thưởng thức vị ngon. Đặt món cà phê, trà, bánh và đồ ăn vặt ngay hôm nay.')

@section('header_style', 'min-height: 100vh; max-height: 1000px;')

@section('header_content')
	<!-- ===== Hero Content ===== -->
	<div class="mt-20 md:mt-[120px] max-w-[557px]">
		<h2 class="hero-title-xin mb-4">Xin chào</h2>
		<h1 class="hero-title-main mb-6">Nạp năng lượng,<br>thưởng thức vị ngon.</h1>
		<p class="hero-desc mb-10 max-w-[431px] opacity-95">
			Dù là một bữa sáng nhanh gọn, một buổi trà chiều thư giãn hay một miếng bánh ngọt ngào cho ngày thêm
			vui, XM Coffee luôn sẵn sàng đồng hành cùng bạn.
		</p>
		<a href="{{ route('menu.index') }}" id="hero-order-btn"
			class="inline-block bg-white text-[#302617] font-outfit font-medium text-lg py-[16px] px-10 rounded-full hover:bg-gray-100 transition shadow-xl hover:scale-105 transform duration-300">
			Xem thực đơn
		</a>
	</div>
@endsection

@section('content')
	<main class="py-16">

		<!-- ============ BEST SELLERS: DRINKS ============ -->
		<section id="best-drinks" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-20 mb-20 relative">
			<div class="text-center mb-12">
				<h2 class="section-title">Đồ bán chạy trong tuần</h2>
			</div>

			<div class="relative">
				<!-- Left Arrow -->
				<button id="drinks-left-arrow" aria-label="Trước" class="arrow-btn hidden lg:flex" style="left: -36px;"
					onclick="slideCarousel('drinks', -1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
					</svg>
				</button>

				<!-- Product Grid -->
				<div id="drinks-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
					@if(isset($bestDrinks) && $bestDrinks->count() > 0)
						@foreach($bestDrinks as $product)
							<div id="product-{{ Str::slug($product->ten_san_pham) }}" class="product-card">
								<div class="relative">
									<img src="{{ $product->image_url }}" class="card-img" alt="{{ $product->ten_san_pham }}" />
									<button class="heart-btn" aria-label="Yêu thích" data-wishlist-id="{{ $product->id }}">
										<svg class="w-7 h-7 text-[#F1F0EE]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
											stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round"
												d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
										</svg>
									</button>
								</div>
								<div class="flex flex-col gap-2">
									<h3 class="product-name">{{ $product->ten_san_pham }}</h3>
									<p class="product-desc">{{ Str::limit($product->mo_ta, 80) }}</p>
								</div>
								<div class="flex items-center justify-between mt-auto pt-2">
									<span
										class="product-price">{{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ</span>
									<button class="product-btn home-add-btn" data-product-id="{{ $product->id }}"
										data-product-name="{{ $product->ten_san_pham }}"
										data-product-img="{{ $product->image_url }}" data-add-url="{{ route('cart.add') }}">Thêm
										món</button>
								</div>
							</div>
						@endforeach
					@else
						<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có sản phẩm bán chạy.</div>
					@endif
				</div>

				<!-- Right Arrow -->
				<button id="drinks-right-arrow" aria-label="Tiếp theo" class="arrow-btn hidden lg:flex"
					style="right: -36px;" onclick="slideCarousel('drinks', 1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
					</svg>
				</button>
			</div>
		</section>

		<!-- ============ BEST SELLERS: DESSERTS ============ -->
		<section id="best-desserts" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-20 mb-20 relative">
			<div class="text-center mb-12">
				<h2 class="section-title">Đồ tráng miệng bán chạy trong tuần</h2>
			</div>

			<div class="relative">
				<!-- Left Arrow -->
				<button id="desserts-left-arrow" aria-label="Trước" class="arrow-btn hidden lg:flex" style="left: -36px;"
					onclick="slideCarousel('desserts', -1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
					</svg>
				</button>

				<!-- Product Grid -->
				<div id="desserts-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
					@if(isset($bestDesserts) && $bestDesserts->count() > 0)
						@foreach($bestDesserts as $product)
							<div id="product-{{ Str::slug($product->ten_san_pham) }}" class="product-card">
								<div class="relative">
									<img src="{{ $product->image_url }}" class="card-img" alt="{{ $product->ten_san_pham }}" />
									<button class="heart-btn" aria-label="Yêu thích" data-wishlist-id="{{ $product->id }}">
										<svg class="w-7 h-7 text-[#F1F0EE]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
											stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round"
												d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
										</svg>
									</button>
								</div>
								<div class="flex flex-col gap-2">
									<h3 class="product-name">{{ $product->ten_san_pham }}</h3>
									<p class="product-desc">{{ Str::limit($product->mo_ta, 80) }}</p>
								</div>
								<div class="flex items-center justify-between mt-auto pt-2">
									<span
										class="product-price">{{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ</span>
									<button class="product-btn home-add-btn" data-product-id="{{ $product->id }}"
										data-product-name="{{ $product->ten_san_pham }}"
										data-product-img="{{ $product->image_url }}" data-add-url="{{ route('cart.add') }}">Thêm
										món</button>
								</div>
							</div>
						@endforeach
					@else
						<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có đồ tráng miệng bán chạy.
						</div>
					@endif
				</div>

				<!-- Right Arrow -->
				<button id="desserts-right-arrow" aria-label="Tiếp theo" class="arrow-btn hidden lg:flex"
					style="right: -36px;" onclick="slideCarousel('desserts', 1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
					</svg>
				</button>
			</div>
		</section>

		<!-- ============ DISCOVER BANNER ============ -->
		<section id="discover-banner" class="bg-[#E2D9C8] mb-20 overflow-hidden">
			<div class="max-w-[1680px] mx-auto grid grid-cols-1 lg:grid-cols-[1fr_2fr_1fr] items-stretch">
				<!-- Left Image -->
				<img src="{{ asset('images/decor1.png') }}" class="w-full h-full object-cover hidden lg:block"
					alt="Coffee Banner Left" />

				<!-- Center Content -->
				<div class="flex flex-col items-center justify-center text-center px-8 py-10 lg:py-12">
					<h2 class="discover-title max-w-xl mb-10">
						Hãy xem qua những hương vị ngon nhất của chúng tôi!
					</h2>
					<a href="{{ route('menu.index') }}" id="discover-btn"
						class="bg-[#30261C] text-[#F1F0EE] inline-flex items-center gap-4 py-4 px-10 rounded-full hover:bg-black hover:scale-105 transition shadow-lg font-outfit font-medium text-lg">
						<span>Khám phá sản phẩm</span>
						<span class="text-2xl font-semibold">>></span>
					</a>
				</div>

				<!-- Right Image -->
				<img src="{{ asset('images/decor2.png') }}" class="w-full h-full object-cover hidden lg:block"
					alt="Coffee Banner Right" />
			</div>
		</section>

		<!-- ============ TESTIMONIALS ============ -->
		<section id="testimonials" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 mb-20">
			<div class="text-center mb-12">
				<p class="testimonial-subtitle mb-2">Hãy đến và tham gia nào</p>
				<h2 class="testimonial-title">Khách hàng yêu quý của chúng tôi</h2>
			</div>

			<div
				class="flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 md:grid md:grid-cols-3 md:overflow-visible md:snap-none">
				@if(isset($testimonials) && $testimonials->count() > 0)
					@foreach($testimonials as $review)
						<div id="testimonial-{{ $review->id }}"
							class="bg-[#E2D9C8]/40 border border-[#30261C]/10 rounded-[20px] p-8 flex flex-col min-w-[280px] snap-start md:min-w-0 {{ $loop->index === 1 ? 'border-2 border-dashed border-[#30261C]/20 transform hover:-translate-y-2 transition duration-300 shadow-md' : '' }}">
							<div class="flex items-start justify-between mb-6">
								<div class="flex items-center gap-4">
									<img src="{{ optional($review->nguoiDung)->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(optional($review->nguoiDung)->ho_ten ?? 'Khach hang') . '&background=E2D9C8&color=30261C' }}"
										class="w-14 h-14 rounded-full object-cover shadow-sm"
										alt="{{ optional($review->nguoiDung)->ho_ten ?? 'Khách hàng' }} Avatar" />
									<div>
										<h4 class="text-[#30261C] text-lg font-bold font-outfit">
											{{ optional($review->nguoiDung)->ho_ten ?? 'Khách hàng' }}
										</h4>
										<p class="text-[#30261C]/80 text-sm font-medium font-poppins">
											{{ optional($review->sanPham)->ten_san_pham ?? '—' }}
										</p>
									</div>
								</div>
								<!-- Stars -->
								<div class="flex items-center gap-0.5">
									@for($i = 0; $i < 5; $i++)
										<svg class="w-5 h-5 {{ $i < ($review->so_sao ?? 0) ? 'text-yellow-500' : 'text-gray-300' }}"
											fill="currentColor" viewBox="0 0 20 20">
											<path
												d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
										</svg>
									@endfor
								</div>
							</div>
							<p class="text-[#30261C] text-base leading-relaxed flex-1 font-outfit">{{ $review->noi_dung ?? '' }}
							</p>
						</div>
					@endforeach
				@else
					<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có đánh giá từ khách hàng.</div>
				@endif
			</div>

			@if(isset($testimonials) && $testimonials->count() > 0)
				@php
					$testimonialCount = $testimonials->count();
					$dotCount = (int) ceil($testimonialCount / 3);
				@endphp
				@if($dotCount > 0)
					<!-- Dot indicators -->
					<div class="dot-row mt-8">
						@for($i = 0; $i < $dotCount; $i++)
							<div class="dot {{ $i === 0 ? 'active' : '' }}"></div>
						@endfor
					</div>
				@endif
			@endif
		</section>

		<!-- ============ NEWSLETTER SECTION ============ -->
		<section id="newsletter" class="bg-[#E2D9C8] overflow-hidden">
			<div class="max-w-[1680px] mx-auto grid grid-cols-1 lg:grid-cols-[1fr_2fr_1fr] items-stretch">
				<!-- Left Image -->
				<img src="{{ asset('images/decor4.png') }}" class="w-full h-full object-cover hidden lg:block"
					alt="Newsletter Left" />

				<!-- Content -->
				<div class="flex flex-col justify-center px-8 lg:px-12 py-12 lg:py-10 w-full max-w-2xl mx-auto">
					<h2 class="newsletter-title mb-4">
						Tham gia ngay và nhận ngay ưu đãi giảm giá 15%!
					</h2>
					<p class="text-[#30261C] text-lg mb-8 opacity-90 font-outfit">
						Đăng ký nhận bản tin của chúng tôi để nhận mã giảm giá 15%.
					</p>
					<form id="newsletter-form" class="flex flex-col sm:flex-row items-center gap-4 w-full"
						action="{{ route('newsletter.subscribe') }}" method="POST" onsubmit="handleNewsletter(event)">
						@csrf
						<div class="flex-1 flex items-center bg-[#F1F0EE] rounded-full px-6 py-1 h-16 w-full shadow-inner">
							<svg class="w-6 h-6 mr-3 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M3 7l9 6 9-6" />
								<rect x="3" y="5" width="18" height="14" rx="2" />
							</svg>
							<input id="newsletter-email" type="email" name="email" placeholder="Địa chỉ Email" required
								class="bg-transparent text-[#30261C] text-lg w-full outline-none placeholder-gray-500 font-outfit" />
						</div>
						<button id="newsletter-submit" type="submit"
							class="bg-[#30261C] text-[#F1F0EE] text-lg font-bold font-outfit h-16 px-10 rounded-full hover:bg-black transition whitespace-nowrap w-full sm:w-auto shadow-md">
							Đặt mua
						</button>
					</form>
				</div>

				<!-- Right Image -->
				<img src="{{ asset('images/decor3.png') }}" class="w-full h-full object-cover hidden lg:block"
					alt="Newsletter Right" />
			</div>
		</section>

	</main>

@endsection

@push('scripts')
	<script>
		function slideCarousel(type, direction) {
			const grid = document.getElementById(type + '-grid');
			if (!grid) return;
			const scrollAmount = grid.clientWidth || 0;
			grid.scrollBy({ left: scrollAmount * direction, behavior: 'smooth' });
		}

		function handleNewsletter() {
			return true;
		}

		document.querySelectorAll('.home-add-btn').forEach(btn => {
			btn.addEventListener('click', function () {
				const productId = this.dataset.productId;
				const addUrl = this.dataset.addUrl;
				const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
				fetch(addUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
					body: JSON.stringify({ product_id: productId, qty: 1 }),
				})
					.then(r => r.json())
					.then(d => {
						if (d.success && window.updateCartBadge) {
							window.updateCartBadge(d.cart_count || 0);
						}
					})
					.catch(err => console.error('Cart error:', err));
			});
		});
	</script>
@endpush