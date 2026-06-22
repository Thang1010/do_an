@extends('customer.layout.app')

@section('title', $product->ten_san_pham . ' - XM Coffee')
@section('meta_description', Str::limit($product->mo_ta ?? '', 160))

@section('header_overlay', 'bg-black/30')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endpush

@section('content')
<div class="show-wrap">

    {{-- Breadcrumb --}}
    <nav class="show-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('menu.index') }}">Menu</a>
        @if($product->danhMuc)
            <span>/</span>
            <a href="{{ route('menu.index', ['category' => Str::slug($product->danhMuc->ten_danh_muc)]) }}">{{ $product->danhMuc->ten_danh_muc }}</a>
        @endif
        <span>/</span>
        <span style="color:rgba(255,255,255,0.9)">{{ $product->ten_san_pham }}</span>
    </nav>

    {{-- Main product card --}}
    <div class="show-card">

        {{-- Gallery --}}
        <div class="show-gallery">
            <img id="show-main-img" src="{{ $product->image_url }}" alt="{{ $product->ten_san_pham }}" class="show-main-img" />
        </div>

        {{-- Info --}}
        <div class="show-info">
            @if($product->danhMuc)
                <p class="show-category">{{ $product->danhMuc->ten_danh_muc }}</p>
            @endif

            <h1 class="show-name">{{ $product->ten_san_pham }}</h1>

            {{-- Stars --}}
            <div class="show-stars">
                @for($i = 1; $i <= 5; $i++)
                    <svg fill="{{ $i <= round($avgRating) ? '#f59e0b' : 'none' }}"
                         stroke="{{ $i <= round($avgRating) ? '#f59e0b' : 'rgba(255,255,255,0.4)' }}"
                         stroke-width="1.5" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                @endfor
                <span class="show-rating-count">
                    {{ $avgRating > 0 ? number_format($avgRating, 1) . ' / 5' : 'Chưa có đánh giá' }}
                    ({{ $product->danhGiaSanPham->count() }} đánh giá)
                </span>
            </div>

            {{-- Price --}}
            @php
                $sizeOrder = ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4];
                $productBasePrice = (float)($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);
                $sizes = $product->kichCo->sortBy(function($kc) use ($sizeOrder) {
                    $code = mb_strtoupper($kc->ma_kich_co ?? '');
                    if (isset($sizeOrder[$code])) return $sizeOrder[$code];
                    $name = mb_strtoupper(mb_substr(trim($kc->ten_kich_co ?? ''), 0, 1));
                    if (isset($sizeOrder[$name])) return $sizeOrder[$name];
                    return $kc->he_so_gia ?? 1;
                });
                $defaultSize = $sizes->first();
                $basePrice = $defaultSize ? $productBasePrice * (float)($defaultSize->he_so_gia ?? 1) : $productBasePrice;
                $originalPrice = $product->gia_goc;
                $hasDiscount = $product->gia_khuyen_mai && $product->gia_khuyen_mai < $product->gia_goc;
            @endphp
            <div class="show-price-row">
                <span class="show-price" id="show-price">{{ number_format($basePrice, 0, ',', '.') }}đ</span>
                @if($hasDiscount)
                    <span class="show-price-original" id="show-price-original">{{ number_format($originalPrice, 0, ',', '.') }}đ</span>
                @endif
            </div>

            {{-- Size selector --}}
            @if($sizes->count() > 1)
                <div>
                    <p class="show-sizes-label">Chọn size</p>
                    <div class="show-sizes">
                        @foreach($sizes as $kc)
                            <button type="button"
                                    class="show-size-btn {{ $loop->first ? 'active' : '' }}"
                                    data-kich-co-id="{{ $kc->id }}"
                                    data-gia="{{ $productBasePrice * (float)($kc->he_so_gia ?? 1) }}"
                                    data-gia-goc="{{ $product->gia_goc * (float)($kc->he_so_gia ?? 1) }}"
                                    onclick="selectSize(this)">
                                {{ $kc->ten_kich_co ?? 'Size ' . $loop->iteration }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Description --}}
            @if($product->mo_ta)
                <p class="show-desc">{{ $product->mo_ta }}</p>
            @endif

            {{-- Actions --}}
            @php
                $isFav = auth()->check() && auth()->user()->sanPhamYeuThich()->where('san_pham_id', $product->id)->exists();
            @endphp
            <div class="show-actions">
                <button type="button"
                        class="show-add-btn"
                        id="show-add-btn"
                        data-product-id="{{ $product->id }}"
                        data-product-img="{{ $product->image_url }}"
                        data-add-url="{{ route('cart.add') }}">
                    Thêm vào giỏ hàng
                </button>
                <button type="button"
                        class="show-heart-btn {{ $isFav ? 'liked' : '' }}"
                        data-wishlist="{{ $product->id }}"
                        aria-label="Yêu thích">
                    <svg class="w-5 h-5" width="20" height="20"
                         fill="{{ $isFav ? '#c94040' : 'none' }}"
                         stroke="{{ $isFav ? '#c94040' : '#fff' }}"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Reviews --}}
    <div class="show-section">
        <h2 class="show-section-title">
            Đánh giá sản phẩm ({{ $product->danhGiaSanPham->count() }})
        </h2>

        @if(session('success'))
            <div style="background:rgba(74,222,128,0.18);border:1px solid rgba(74,222,128,0.4);border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-family:'Outfit',sans-serif;font-size:.9rem;color:#bbf7d0;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background:rgba(248,113,113,0.18);border:1px solid rgba(248,113,113,0.4);border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-family:'Outfit',sans-serif;font-size:.9rem;color:#fecaca;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Review list --}}
        @if($product->danhGiaSanPham->count() > 0)
            <div class="review-list">
                @foreach($product->danhGiaSanPham->sortByDesc('created_at') as $review)
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-avatar">
                                {{ mb_substr($review->nguoiDung?->hoSoKhachHang?->ho_ten ?? $review->nguoiDung?->email ?? 'K', 0, 1) }}
                            </div>
                            <span class="review-author">{{ $review->nguoiDung?->hoSoKhachHang?->ho_ten ?? $review->nguoiDung?->email ?? 'Khách hàng' }}</span>
                            <span class="review-date">{{ $review->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="review-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <svg fill="{{ $i <= $review->so_sao ? '#f59e0b' : 'rgba(255,255,255,0.2)' }}"
                                     viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        @if($review->noi_dung)
                            <p class="review-text">{{ $review->noi_dung }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="review-empty">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        @endif

        {{-- Review form --}}
        <div style="margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid rgba(255,255,255,0.12);">
            <h3 class="show-section-title" style="font-size:1rem;margin-bottom:1rem;padding-bottom:0;border:none;">
                Viết đánh giá của bạn
            </h3>
            @auth
                @if($hasBought)
                    <form method="POST" action="{{ route('menu.review.store', $product->id) }}" class="review-form">
                        @csrf
                        <div>
                            <p style="font-family:'Outfit',sans-serif;font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:.4rem;">Số sao</p>
                            <div class="star-picker">
                                @for($s = 5; $s >= 1; $s--)
                                    <input type="radio" name="so_sao" id="star{{ $s }}" value="{{ $s }}"
                                           {{ old('so_sao') == $s ? 'checked' : '' }} required>
                                    <label for="star{{ $s }}" title="{{ $s }} sao">★</label>
                                @endfor
                            </div>
                            @error('so_sao')
                                <p style="color:#fca5a5;font-size:.8rem;margin-top:.3rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <textarea name="noi_dung" class="review-textarea"
                                      placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."
                                      maxlength="1000">{{ old('noi_dung') }}</textarea>
                            @error('noi_dung')
                                <p style="color:#fca5a5;font-size:.8rem;margin-top:.3rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="review-submit-btn">Gửi đánh giá</button>
                    </form>
                @elseif($reviewExpired ?? false)
                    <p style="font-family:'Outfit',sans-serif;font-size:.9rem;color:rgba(255,255,255,.65);">
                        Đã quá thời hạn đánh giá. Bạn chỉ có thể đánh giá sản phẩm trong ngày mua hàng.
                    </p>
                @else
                    <p style="font-family:'Outfit',sans-serif;font-size:.9rem;color:rgba(255,255,255,.65);">
                        Bạn cần mua sản phẩm này thì mới có thể viết đánh giá.
                    </p>
                @endif
            @else
                <p style="font-family:'Outfit',sans-serif;font-size:.9rem;color:rgba(255,255,255,.65);">
                    <a href="{{ route('auth.login') }}" style="color:#f5e6c8;text-decoration:underline;">Đăng nhập</a>
                    để viết đánh giá.
                </p>
            @endauth
        </div>
    </div>

    {{-- Related products --}}
    @if($related->count() > 0)
        <div class="show-section">
            <h2 class="show-section-title">Sản phẩm liên quan</h2>
            <div class="related-grid">
                @foreach($related as $rel)
                    <div class="menu-card" style="cursor:pointer;" data-href="{{ route('menu.show', $rel->id) }}">
                        <div class="menu-card-img-wrap">
                            <img src="{{ $rel->image_url }}" alt="{{ $rel->ten_san_pham }}" class="menu-card-img" loading="lazy" />
                        </div>
                        <div class="menu-card-body">
                            <h3 class="menu-card-name">{{ $rel->ten_san_pham }}</h3>
                            {{-- related stars --}}
                            @php $relRating = round($rel->avg_rating ?? 0); @endphp
                            <div style="display:flex;gap:2px;margin-bottom:.3rem;">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg width="12" height="12" viewBox="0 0 20 20"
                                         fill="{{ $i <= $relRating ? '#f59e0b' : 'rgba(255,255,255,0.2)' }}">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <div class="menu-card-footer">
                                <span class="menu-card-price">
                                    {{ number_format($rel->gia_khuyen_mai ?? $rel->gia_goc, 0, ',', '.') }}đ
                                </span>
                                <button class="menu-card-btn menu-add-btn"
                                        data-product-id="{{ $rel->id }}"
                                        data-product-img="{{ $rel->image_url }}"
                                        data-add-url="{{ route('cart.add') }}"
                                        onclick="event.stopPropagation();">
                                    Thêm món
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
@php $defaultKichCoId = $sizes->count() > 1 ? $sizes->first()->kich_co_id : ''; @endphp
<script>
    // Gallery thumbnail switcher
    function switchImage(thumb, src) {
        document.getElementById('show-main-img').src = src;
        document.querySelectorAll('.show-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }

    // Size selector — update price display
    const selectedKichCoInput = document.createElement('input');
    selectedKichCoInput.type = 'hidden';
    selectedKichCoInput.name = 'kich_co_id';
    selectedKichCoInput.id = 'selected-kich-co';
    selectedKichCoInput.value = '{{ $defaultKichCoId }}';

    function selectSize(btn) {
        document.querySelectorAll('.show-size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const price = parseFloat(btn.dataset.gia);
        const orig = parseFloat(btn.dataset.giaGoc);
        const priceEl = document.getElementById('show-price');
        const origEl = document.getElementById('show-price-original');
        if (priceEl) priceEl.textContent = price.toLocaleString('vi-VN') + 'đ';
        if (origEl) {
            if (orig && orig > price) {
                origEl.textContent = orig.toLocaleString('vi-VN') + 'đ';
                origEl.style.display = '';
            } else {
                origEl.style.display = 'none';
            }
        }
        selectedKichCoInput.value = btn.dataset.kichCoId || '';
    }

    // Add to cart
    function launchCartAnimation(imgSrc) {
        const mainImg = document.getElementById('show-main-img');
        const cartBtnDesktop = document.getElementById('cart-btn');
        const cartBtnMobile = document.getElementById('cart-btn-mobile');
        let targetCart = cartBtnDesktop;
        if (cartBtnMobile) {
            const mobileWrap = cartBtnMobile.closest('.lg\\:hidden') || cartBtnMobile.parentElement;
            if (mobileWrap && getComputedStyle(mobileWrap).display !== 'none') targetCart = cartBtnMobile;
        }
        if (!targetCart || !mainImg) return;
        const imgRect = mainImg.getBoundingClientRect();
        const cartRect = targetCart.getBoundingClientRect();
        const wrapper = document.createElement('div');
        wrapper.style.cssText = `position:fixed;left:${imgRect.left}px;top:${imgRect.top}px;width:${Math.min(imgRect.width,80)}px;height:${Math.min(imgRect.height,80)}px;z-index:9999;pointer-events:none;`;
        const ghost = document.createElement('img');
        ghost.src = imgSrc;
        ghost.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;box-shadow:0 10px 25px rgba(0,0,0,.3);';
        wrapper.appendChild(ghost);
        document.body.appendChild(wrapper);
        const deltaX = (cartRect.left + cartRect.width/2) - (imgRect.left + Math.min(imgRect.width,80)/2);
        const deltaY = (cartRect.top + cartRect.height/2) - (imgRect.top + Math.min(imgRect.height,80)/2);
        const dur = 750;
        wrapper.animate([{transform:'translateX(0)'},{transform:`translateX(${deltaX}px)`}], {duration:dur,easing:'linear',fill:'forwards'});
        ghost.animate([
            {transform:'translateY(0) scale(1)',opacity:.95},
            {transform:`translateY(${deltaY-80}px) scale(0.5)`,opacity:.7,offset:.4},
            {transform:`translateY(${deltaY}px) scale(0.15)`,opacity:0}
        ], {duration:dur,easing:'ease-in-out',fill:'forwards'});
        setTimeout(() => {
            wrapper.remove();
            targetCart.classList.add('cart-bounce');
            setTimeout(() => targetCart.classList.remove('cart-bounce'), 500);
        }, dur);
    }

    const updateCartBadge = window.updateCartBadge || function(count) {
        document.querySelectorAll('.cart-count-badge').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    };

    document.getElementById('show-add-btn')?.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const imgSrc = this.dataset.productImg;
        const addUrl = this.dataset.addUrl;
        const kichCoId = selectedKichCoInput.value;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        launchCartAnimation(imgSrc);
        const body = { product_id: productId, qty: 1 };
        if (kichCoId) body.size_id = kichCoId;
        fetch(addUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        }).then(r => r.json()).then(d => { if (d.success) updateCartBadge(d.cart_count || 0); })
          .catch(err => console.error('Cart error:', err));
    });

    // Điều hướng khi bấm thẻ sản phẩm liên quan (dùng data-href thay vì onclick nội tuyến)
    document.querySelectorAll('.menu-card[data-href]').forEach(card => {
        card.addEventListener('click', () => { window.location = card.dataset.href; });
    });

    // Heart / wishlist
    document.querySelector('.show-heart-btn')?.addEventListener('click', function() {
        const productId = this.getAttribute('data-wishlist');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(`/menu/${productId}/favorite`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' }
        }).then(r => {
            if (r.status === 401) { showNotice('Bạn cần đăng nhập để yêu thích sản phẩm.'); return Promise.reject(); }
            return r.json();
        }).then(data => {
            if (data && data.success) {
                const svg = this.querySelector('svg');
                if (data.is_favorite) {
                    this.classList.add('liked');
                    if (svg) { svg.setAttribute('fill', '#c94040'); svg.setAttribute('stroke', '#c94040'); }
                } else {
                    this.classList.remove('liked');
                    if (svg) { svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', '#fff'); }
                }
            }
        }).catch(() => {});
    });

    // Related product add-to-cart (reuse launchCartAnimation)
    document.querySelectorAll('.menu-add-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.dataset.productId;
            const imgSrc = this.dataset.productImg;
            const addUrl = this.dataset.addUrl;
            const imgEl = this.closest('.menu-card')?.querySelector('.menu-card-img');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            // simple animation from card image
            if (imgEl) {
                const imgRect = imgEl.getBoundingClientRect();
                const cartBtnDesktop = document.getElementById('cart-btn');
                if (cartBtnDesktop) {
                    const cartRect = cartBtnDesktop.getBoundingClientRect();
                    const wrapper = document.createElement('div');
                    wrapper.style.cssText = `position:fixed;left:${imgRect.left}px;top:${imgRect.top}px;width:${imgRect.width}px;height:${imgRect.height}px;z-index:9999;pointer-events:none;`;
                    const ghost = document.createElement('img');
                    ghost.src = imgSrc; ghost.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;box-shadow:0 10px 25px rgba(0,0,0,.3);';
                    wrapper.appendChild(ghost); document.body.appendChild(wrapper);
                    const dX = (cartRect.left+cartRect.width/2)-(imgRect.left+imgRect.width/2);
                    const dY = (cartRect.top+cartRect.height/2)-(imgRect.top+imgRect.height/2);
                    wrapper.animate([{transform:'translateX(0)'},{transform:`translateX(${dX}px)`}],{duration:750,easing:'linear',fill:'forwards'});
                    ghost.animate([{transform:'translateY(0) scale(1)',opacity:.95},{transform:`translateY(${dY-80}px) scale(0.5)`,opacity:.7,offset:.4},{transform:`translateY(${dY}px) scale(0.15)`,opacity:0}],{duration:750,easing:'ease-in-out',fill:'forwards'});
                    setTimeout(()=>{wrapper.remove();},750);
                }
            }
            fetch(addUrl, {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                body:JSON.stringify({product_id:productId,qty:1})
            }).then(r=>r.json()).then(d=>{if(d.success)updateCartBadge(d.cart_count||0);}).catch(err=>console.error(err));
        });
    });
</script>
@endpush

