@extends('customer.layout.app')

@section('title', 'Menu - XM Coffee')
@section('meta_description', 'Khám phá thực đơn phong phú của XM Coffee - Cà phê, Trà, Bánh và Đồ ăn vặt.')

@section('header_overlay', 'bg-black/30')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
@endpush

@section('content')
    <!-- ============ MAIN — 2 glass panels ============ -->
    <div class="menu-main">

        <!-- ── Panel Trái: Sidebar danh mục ── -->
        <aside class="glass-sidebar">
            <p class="sidebar-cat-label">Danh mục</p>
            @foreach($categories as $category)
                @php
                    $slug = $categorySlugs[$category->id] ?? \Illuminate\Support\Str::slug($category->ten_danh_muc);
                    $isActive = $activeCategory && $activeCategory->id === $category->id;
                @endphp
                <a href="{{ route('menu.index', ['category' => $slug]) }}"
                    class="sidebar-cat-item {{ $isActive ? 'active' : '' }}">
                    {{ $category->ten_danh_muc }}
                </a>
            @endforeach
        </aside>

        <!-- ── Panel Phải: Sản phẩm ── -->
        <section class="glass-content">

            <!-- Tiêu đề + search bar -->
            <div class="glass-title-bar">
                <div class="glass-title-group">
                    <h1 class="glass-cat-title">
                        @if($search !== '')
                            Tìm kiếm: "{{ $search }}"
                        @elseif($activeCategory)
                            {{ $activeCategory->ten_danh_muc }}
                        @else
                            Tất cả món
                        @endif
                    </h1>
                    <p class="glass-product-count">{{ $products->total() }} sản phẩm</p>
                </div>

                <!-- search -->
                <form action="{{ route('menu.index') }}" method="GET" class="glass-search-form">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Tìm kiếm theo tên sản phẩm..."
                        class="glass-search-input" />
                    @if($activeCategory && $search === '')
                        <input type="hidden" name="category"
                            value="{{ $categorySlugs[$activeCategory->id] ?? \Illuminate\Support\Str::slug($activeCategory->ten_danh_muc) }}" />
                    @endif
                    <button type="submit" class="glass-search-btn" aria-label="Tìm kiếm">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Lưới sản phẩm -->
            <div id="products-container">
                <div class="products-grid">
                    @forelse($products as $product)
                        <div class="menu-card">
                            <!-- Image -->
                            <div class="menu-card-img-wrap">
                                <img src="{{ $product->image_url }}" alt="{{ $product->ten_san_pham }}" class="menu-card-img"
                                    loading="lazy" />
                                @php $isFav = isset($product->is_favorite) && $product->is_favorite; @endphp
                                <button class="menu-card-heart {{ $isFav ? 'liked' : '' }}" aria-label="Yêu thích"
                                    data-wishlist="{{ $product->id }}">
                                    <svg class="w-4 h-4" fill="{{ $isFav ? '#c94040' : 'none' }}" viewBox="0 0 24 24"
                                        stroke="{{ $isFav ? '#c94040' : '#5a3520' }}" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="menu-card-body">
                                <h3 class="menu-card-name">{{ $product->ten_san_pham }}</h3>
                                <div class="menu-card-footer">
                                    <span class="menu-card-price">
                                        {{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ
                                    </span>
                                    <button class="menu-card-btn menu-add-btn" data-product-id="{{ $product->id }}"
                                        data-product-img="{{ $product->image_url }}" data-add-url="{{ route('cart.add') }}">Thêm
                                        món</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="menu-empty">
                            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,0.35)"
                                stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>Không có sản phẩm nào trong danh mục này.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($products->hasPages())
                    <nav class="menu-pagination" aria-label="Phân trang">
                        @if($products->onFirstPage())
                            <span class="disabled">&lsaquo;</span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}">&lsaquo;</a>
                        @endif

                        @foreach($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                            @if($page == $products->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}">&rsaquo;</a>
                        @else
                            <span class="disabled">&rsaquo;</span>
                        @endif
                    </nav>
                @endif
            </div>

            <!-- Ngôi sao trang trí -->
            <div class="glass-star">✦</div>
        </section>

    </div><!-- end menu-main -->
@endsection

@push('scripts')
    <script>
        // Heart toggle via AJAX
        document.querySelectorAll('.menu-card-heart').forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-wishlist');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(`/menu/${productId}/favorite`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => {
                        if (response.status === 401) {
                            alert('Bạn cần đăng nhập để yêu thích sản phẩm.');
                            return Promise.reject('Unauthorized');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            // Đẩy lên đầu = reload lại trang để server sắp xếp lại
                            window.location.reload();
                        }
                    })
                    .catch(err => console.error(err));
            });
        });

        // Fade khi đổi danh mục
        document.querySelectorAll('.sidebar-cat-item').forEach(item => {
            item.addEventListener('click', () => {
                document.getElementById('products-container')?.classList.add('fading');
            });
        });

        // ── Hàm animation parabol dùng chung ──────────────────────
        function launchCartAnimation(imgEl, imgSrc) {
            const cartBtnDesktop = document.getElementById('cart-btn');
            const cartBtnMobile = document.getElementById('cart-btn-mobile');
            let targetCart = cartBtnDesktop;
            if (cartBtnMobile) {
                const mobileWrap = cartBtnMobile.closest('.lg\\:hidden') || cartBtnMobile.parentElement;
                if (mobileWrap && getComputedStyle(mobileWrap).display !== 'none') targetCart = cartBtnMobile;
            }
            if (!targetCart) return;

            const imgRect = imgEl ? imgEl.getBoundingClientRect() : { left: window.innerWidth / 2, top: window.innerHeight / 2, width: 60, height: 60 };
            const cartRect = targetCart.getBoundingClientRect();

            const wrapper = document.createElement('div');
            wrapper.style.cssText = `position:fixed;left:${imgRect.left}px;top:${imgRect.top}px;width:${imgRect.width}px;height:${imgRect.height}px;z-index:9999;pointer-events:none;`;

            const ghost = document.createElement('img');
            ghost.src = imgSrc;
            ghost.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;box-shadow:0 10px 25px rgba(0,0,0,.3);';
            wrapper.appendChild(ghost);
            document.body.appendChild(wrapper);

            const deltaX = (cartRect.left + cartRect.width / 2) - (imgRect.left + imgRect.width / 2);
            const deltaY = (cartRect.top + cartRect.height / 2) - (imgRect.top + imgRect.height / 2);
            const dur = 750;

            wrapper.animate([{ transform: 'translateX(0)' }, { transform: `translateX(${deltaX}px)` }],
                { duration: dur, easing: 'linear', fill: 'forwards' });
            ghost.animate([
                { transform: 'translateY(0) scale(1)', opacity: 0.95 },
                { transform: `translateY(${deltaY - 80}px) scale(0.5)`, opacity: 0.7, offset: 0.4 },
                { transform: `translateY(${deltaY}px) scale(0.15)`, opacity: 0 },
            ], { duration: dur, easing: 'ease-in-out', fill: 'forwards' });

            setTimeout(() => {
                wrapper.remove();
                targetCart.classList.add('cart-bounce');
                setTimeout(() => targetCart.classList.remove('cart-bounce'), 500);
            }, dur);
        }

        // ── Badge cập nhật ─────────────────────────────────────────
        const updateCartBadge = window.updateCartBadge || function (count) {
            document.querySelectorAll('.cart-count-badge').forEach(el => {
                el.textContent = count;
                el.style.display = count > 0 ? 'flex' : 'none';
            });
        };

        // ── AJAX "Thêm món" ────────────────────────────────────────
        document.querySelectorAll('.menu-add-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const productId = this.dataset.productId;
                const imgSrc = this.dataset.productImg;
                const addUrl = this.dataset.addUrl;
                const imgEl = this.closest('.menu-card')?.querySelector('.menu-card-img');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                launchCartAnimation(imgEl, imgSrc);

                fetch(addUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ product_id: productId, qty: 1 }),
                })
                    .then(r => r.json())
                    .then(d => { if (d.success) updateCartBadge(d.cart_count || 0); })
                    .catch(err => console.error('Cart error:', err));
            });
        });
    </script>
@endpush