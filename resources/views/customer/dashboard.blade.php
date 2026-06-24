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
			{{ \App\Models\CuaHang::first()?->mo_ta ?: 'Dù là một bữa sáng nhanh gọn, một buổi trà chiều thư giãn hay một miếng bánh ngọt ngào cho ngày thêm vui, XM Coffee luôn sẵn sàng đồng hành cùng bạn.' }}
		</p>
		<a href="{{ route('menu.index') }}" id="hero-order-btn"
			class="inline-block bg-white text-[#302617] font-outfit font-medium text-lg py-[16px] px-10 rounded-full hover:bg-gray-100 transition shadow-xl hover:scale-105 transform duration-300">
			Xem thực đơn
		</a>
	</div>
@endsection

@section('content')
	<script>document.documentElement.classList.add('reveal-on');</script>
	<main class="py-16">

		<!-- ============ BEST SELLERS: ALL ============ -->
		<section id="best-sellers" class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-20 mb-20 relative">
			<div class="text-center mb-12 reveal">
				<h2 class="section-title">Top 10 món bán chạy nhất trong tuần</h2>
			</div>

			<div class="relative">
				<!-- Left Arrow -->
				<button id="sellers-left-arrow" aria-label="Trước" class="arrow-btn hidden lg:flex" style="left: -36px;"
					onclick="slideCarousel('sellers', -1)">
					<svg class="w-6 h-6 text-[#30261C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
					</svg>
				</button>

				<!-- Product Grid -->
				<div id="sellers-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 reveal-stagger">
					@if(isset($bestSellers) && $bestSellers->count() > 0)
						@foreach($bestSellers as $product)
							<div id="product-{{ Str::slug($product->ten_san_pham) }}" class="product-card cursor-pointer"
								onclick="window.location='{{ route('menu.show', $product->id) }}'">
								<div class="relative">
									<img src="{{ $product->image_url }}" class="card-img" alt="{{ $product->ten_san_pham }}" />
									<button class="heart-btn" aria-label="Yêu thích" data-wishlist-id="{{ $product->id }}"
										onclick="event.stopPropagation();">
										<svg class="w-7 h-7 text-[#F1F0EE]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
											stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round"
												d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
										</svg>
									</button>
								</div>
								<div class="flex flex-col gap-1">
									<h3 class="product-name">{{ $product->ten_san_pham }}</h3>
									@php $avgRating = round($product->avg_rating ?? 0, 1); @endphp
									<div class="flex items-center gap-1">
										@for($i = 1; $i <= 5; $i++)
											<svg class="w-4 h-4 {{ $i <= round($avgRating) ? 'text-yellow-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
												<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
											</svg>
										@endfor
										<span class="text-xs text-[#30261C]/60 ml-1 font-outfit">{{ $avgRating > 0 ? number_format($avgRating, 1) : '' }}</span>
									</div>
								</div>
								<div class="flex items-center justify-between mt-auto pt-2">
									<span
										class="product-price">{{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ</span>
									<button class="product-btn home-add-btn" data-product-id="{{ $product->id }}"
										data-product-name="{{ $product->ten_san_pham }}"
										data-product-img="{{ $product->image_url }}" data-add-url="{{ route('cart.add') }}"									data-sizes="{{ json_encode($product->kichCo->map(fn($kc) => ['id' => $kc->id, 'name' => $kc->ten_kich_co, 'code' => $kc->ma_kich_co ?? '', 'price' => (float)(($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc) * ($kc->he_so_gia ?? 1))])->values()) }}"
										data-nhiet-do="{{ $product->nhiet_do ?? '' }}"
										onclick="event.stopPropagation();">Thêm
										món</button>
								</div>
							</div>
						@endforeach
					@else
						<div class="col-span-full text-center text-sm text-[#30261C]/70">Chưa có sản phẩm bán chạy.</div>
					@endif
				</div>

				<!-- Right Arrow -->
				<button id="sellers-right-arrow" aria-label="Tiếp theo" class="arrow-btn hidden lg:flex"
					style="right: -36px;" onclick="slideCarousel('sellers', 1)">
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
				<div class="flex flex-col items-center justify-center text-center px-8 py-10 lg:py-12 reveal">
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
			<div class="text-center mb-12 reveal">
				<p class="testimonial-subtitle mb-2">Hãy đến và tham gia nào</p>
				<h2 class="testimonial-title">Khách hàng yêu quý của chúng tôi</h2>
			</div>

			<div
				class="flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 md:grid md:grid-cols-3 md:overflow-visible md:snap-none reveal-stagger">
				@if(isset($testimonials) && $testimonials->count() > 0)
					@foreach($testimonials as $review)
						<div id="testimonial-{{ $review->id }}"
							class="bg-[#E2D9C8]/40 border border-[#30261C]/10 rounded-[20px] p-8 flex flex-col min-w-[280px] snap-start md:min-w-0 {{ $loop->index === 1 ? 'border-2 border-dashed border-[#30261C]/20 transform hover:-translate-y-2 transition duration-300 shadow-md' : '' }}">
							<div class="flex items-start justify-between mb-6">
								<div class="flex items-center gap-4">
								<img src="{{ optional($review->nguoiDung)->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($review->nguoiDung?->hoSoKhachHang?->ho_ten ?? 'Khach hang') . '&background=E2D9C8&color=30261C' }}"
									class="w-14 h-14 rounded-full object-cover shadow-sm"
									alt="{{ $review->nguoiDung?->hoSoKhachHang?->ho_ten ?? 'Khách hàng' }} Avatar" />
								<div>
									<h4 class="text-[#30261C] text-lg font-bold font-outfit">
										{{ $review->nguoiDung?->hoSoKhachHang?->ho_ten ?? $review->nguoiDung?->email ?? 'Khách hàng' }}
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
				<div class="flex flex-col justify-center px-8 lg:px-12 py-12 lg:py-10 w-full max-w-2xl mx-auto reveal">
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
						<a href="{{ route('auth.register') }}"
							onclick="event.preventDefault(); var em=document.getElementById('newsletter-email').value; window.location.href='{{ route('auth.register') }}'+(em?'?email='+encodeURIComponent(em):'');"
							class="bg-[#30261C] text-[#F1F0EE] text-lg font-bold font-outfit h-16 px-10 rounded-full hover:bg-black transition whitespace-nowrap w-full sm:w-auto shadow-md flex items-center justify-center">
							Đăng ký ngay
						</a>
					</form>
				</div>

				<!-- Right Image -->
				<img src="{{ asset('images/decor3.png') }}" class="w-full h-full object-cover hidden lg:block"
					alt="Newsletter Right" />
			</div>
		</section>

	</main>

@endsection

<div id="guest-heart-toast" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#fff;border:1.5px solid #E2D9C8;border-radius:50px;padding:14px 24px;font-family:'Outfit',sans-serif;font-size:15px;color:#30261C;box-shadow:0 4px 24px rgba(0,0,0,0.10);z-index:9999;opacity:0;transition:opacity 0.3s,transform 0.3s;pointer-events:none;white-space:nowrap;">
	🥺 Bạn hãy đăng nhập để yêu thích sản phẩm nhé!
</div>

<div id="guest-heart-toast" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#fff;border:1.5px solid #E2D9C8;border-radius:50px;padding:14px 24px;font-family:'Outfit',sans-serif;font-size:15px;color:#30261C;box-shadow:0 4px 24px rgba(0,0,0,0.10);z-index:9999;opacity:0;transition:opacity 0.3s,transform 0.3s;pointer-events:none;white-space:nowrap;">
	🥺 Bạn hãy đăng nhập để yêu thích sản phẩm nhé!
</div>

@push('styles')
<style>
#guest-heart-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

/* ===== Scroll reveal: chỉ kích hoạt khi có JS (.reveal-on) để tránh ẩn nội dung khi tắt JS ===== */
html { scroll-behavior: smooth; }

.reveal-on .reveal {
	opacity: 0;
	transform: translateY(36px);
	transition: opacity .8s cubic-bezier(.22,.61,.36,1), transform .8s cubic-bezier(.22,.61,.36,1);
	will-change: opacity, transform;
}
.reveal-on .reveal.is-visible {
	opacity: 1;
	transform: none;
}

/* Hiệu ứng so le cho các phần tử con (thẻ sản phẩm, đánh giá) */
.reveal-on .reveal-stagger > * {
	opacity: 0;
	transform: translateY(28px);
	transition: opacity .6s ease-out, transform .6s ease-out;
	will-change: opacity, transform;
}
.reveal-on .reveal-stagger.is-visible > * {
	opacity: 1;
	transform: none;
}
.reveal-on .reveal-stagger.is-visible > *:nth-child(1) { transition-delay: .05s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(2) { transition-delay: .12s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(3) { transition-delay: .19s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(4) { transition-delay: .26s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(5) { transition-delay: .33s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(6) { transition-delay: .40s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(7) { transition-delay: .47s; }
.reveal-on .reveal-stagger.is-visible > *:nth-child(n+8) { transition-delay: .54s; }

/* Tôn trọng tùy chọn giảm chuyển động của người dùng */
@media (prefers-reduced-motion: reduce) {
	html { scroll-behavior: auto; }
	.reveal-on .reveal,
	.reveal-on .reveal-stagger > * {
		opacity: 1 !important;
		transform: none !important;
		transition: none !important;
	}
}
</style>
@endpush

@push('scripts')
	<script>
		function slideCarousel(type, direction) {
			const grid = document.getElementById(type + '-grid');
			if (!grid) return;
			const scrollAmount = grid.clientWidth || 0;
			grid.scrollBy({ left: scrollAmount * direction, behavior: 'smooth' });
		}

		var isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};

		function showGuestHeartToast() {
			var toast = document.getElementById('guest-heart-toast');
			if (!toast) return;
			toast.classList.add('show');
			setTimeout(function() { toast.classList.remove('show'); }, 3000);
		}

		document.querySelectorAll('.heart-btn').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.stopPropagation();
				if (!isAuthenticated) {
					showGuestHeartToast();
					return;
				}
				var productId = this.dataset.wishlistId;
				var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
				fetch('/menu/' + productId + '/favorite', {
					method: 'POST',
					headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' }
				}).then(r => r.json()).then(d => {
					if (d.success) {
						var svg = this.querySelector('svg');
						if (svg) {
							svg.style.fill = d.is_favorite ? '#c94040' : 'none';
							svg.style.stroke = d.is_favorite ? '#c94040' : 'currentColor';
						}
					}
				}).catch(err => console.error(err));
			}.bind(btn));
		});

		// ── Fly-to-cart animation (same as menu) ───────────────
		function launchCartAnimation(imgEl, imgSrc) {
			var cartBtnDesktop = document.getElementById('cart-btn');
			var cartBtnMobile  = document.getElementById('cart-btn-mobile');
			var targetCart = cartBtnDesktop;
			if (cartBtnMobile) {
				var mobileWrap = cartBtnMobile.closest('.lg\\:hidden') || cartBtnMobile.parentElement;
				if (mobileWrap && getComputedStyle(mobileWrap).display !== 'none') targetCart = cartBtnMobile;
			}
			if (!targetCart) return;

			var imgRect  = imgEl ? imgEl.getBoundingClientRect() : { left: window.innerWidth / 2, top: window.innerHeight / 2, width: 60, height: 60 };
			var cartRect = targetCart.getBoundingClientRect();

			var wrapper = document.createElement('div');
			wrapper.style.cssText = 'position:fixed;left:' + imgRect.left + 'px;top:' + imgRect.top + 'px;width:' + imgRect.width + 'px;height:' + imgRect.height + 'px;z-index:9999;pointer-events:none;';

			var ghost = document.createElement('img');
			ghost.src = imgSrc;
			ghost.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;box-shadow:0 10px 25px rgba(0,0,0,.3);';
			wrapper.appendChild(ghost);
			document.body.appendChild(wrapper);

			var deltaX = (cartRect.left + cartRect.width / 2) - (imgRect.left + imgRect.width / 2);
			var deltaY = (cartRect.top  + cartRect.height / 2) - (imgRect.top  + imgRect.height / 2);
			var dur = 750;

			wrapper.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(' + deltaX + 'px)' }],
				{ duration: dur, easing: 'linear', fill: 'forwards' });
			ghost.animate([
				{ transform: 'translateY(0) scale(1)', opacity: 0.95 },
				{ transform: 'translateY(' + (deltaY - 80) + 'px) scale(0.5)', opacity: 0.7, offset: 0.4 },
				{ transform: 'translateY(' + deltaY + 'px) scale(0.15)', opacity: 0 },
			], { duration: dur, easing: 'ease-in-out', fill: 'forwards' });

			setTimeout(function () {
				wrapper.remove();
				targetCart.classList.add('cart-bounce');
				setTimeout(function () { targetCart.classList.remove('cart-bounce'); }, 500);
			}, dur);
		}

		var updateCartBadge = window.updateCartBadge || function (count) {
			document.querySelectorAll('.cart-count-badge').forEach(function (el) {
				el.textContent = count;
				el.style.display = count > 0 ? 'flex' : 'none';
			});
		};

		function doAddToCart(productId, sizeId, addUrl, imgEl, imgSrc) {
			var body = { product_id: productId, qty: 1 };
			if (sizeId) body.size_id = sizeId;
			var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
			fetch(addUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
				body: JSON.stringify(body),
			})
				.then(function (r) { return r.json(); })
				.then(function (d) { if (d.success && window.updateCartBadge) window.updateCartBadge(d.cart_count || 0); })
				.catch(function (err) { console.error('Cart error:', err); });
		}

		// ── Size picker state handled globally in layout ────────

		// ── "Thêm món" button handler ────────────────────────
		document.querySelectorAll('.home-add-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var productId   = this.dataset.productId;
				var productName = this.dataset.productName;
				var imgSrc      = this.dataset.productImg;
				var addUrl      = this.dataset.addUrl;
				var imgEl       = this.closest('.product-card')?.querySelector('.card-img') || null;

				var sizes = [];
				try { sizes = JSON.parse(this.dataset.sizes || '[]'); } catch (e) {}
				var nhietDo = this.dataset.nhietDo || null;

				if (typeof window.showGlobalSizeModal === 'function') {
					window.showGlobalSizeModal(productId, productName, imgSrc, addUrl, sizes, imgEl, nhietDo);
				}
			});
		});

		// ── Scroll reveal: hiện dần các section khi cuộn tới ──────
		(function () {
			var els = document.querySelectorAll('.reveal, .reveal-stagger');
			if (!els.length) return;

			// Trình duyệt không hỗ trợ IntersectionObserver → hiện hết, không ẩn nội dung.
			if (!('IntersectionObserver' in window)) {
				els.forEach(function (el) { el.classList.add('is-visible'); });
				return;
			}

			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			}, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });

			els.forEach(function (el) { observer.observe(el); });
		})();
	</script>
@endpush