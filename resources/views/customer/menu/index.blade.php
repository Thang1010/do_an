@extends('customer.layout.app')

@section('title', 'Menu - XM Coffee')
@section('meta_description', 'Khám phá thực đơn phong phú của XM Coffee - Cà phê, Trà, Bánh và Đồ ăn vặt.')

@section('header_overlay', 'bg-black/30')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <style>
        .qr-table-banner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            max-width: 1680px;
            margin: 0 auto 16px;
            padding: 12px 20px;
            border-radius: 14px;
            background: rgba(226, 217, 200, 0.9);
            border: 1px solid rgba(48, 38, 28, 0.15);
            color: #30261C;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            box-shadow: 0 6px 20px rgba(48, 38, 28, 0.12);
        }
        .qr-table-banner svg { width: 22px; height: 22px; flex-shrink: 0; }
        .qr-table-banner strong { font-weight: 700; }
    </style>
@endpush

@section('content')
    @if(!empty($qrTable))
        <!-- Banner: khách vào menu bằng QR tại bàn -->
        <div class="qr-table-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
                <line x1="6" y1="1" x2="6" y2="4" />
                <line x1="10" y1="1" x2="10" y2="4" />
                <line x1="14" y1="1" x2="14" y2="4" />
            </svg>
            <span>Bạn đang gọi món tại <strong>Bàn {{ $qrTable->so_ban }}</strong></span>
        </div>
    @endif

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
            @include('customer.menu.partials._content')
        </section>

    </div><!-- end menu-main -->

@endsection

@push('scripts')
    <script>
        const glassContent = document.querySelector('.glass-content');

        // ── Nạp lưới sản phẩm qua AJAX (đổi danh mục / tìm kiếm / phân trang) ──
        // Chỉ thay riêng #products-container, giữ nguyên nền + tiêu đề + thanh tìm kiếm.
        let menuReq = 0; // chống race khi bấm nhanh nhiều danh mục
        function loadMenu(url, push = true) {
            const container = document.getElementById('products-container');
            container?.classList.add('fading');
            const reqId = ++menuReq;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    if (reqId !== menuReq) return; // đã có request mới hơn → bỏ qua

                    const tpl = document.createElement('template');
                    tpl.innerHTML = html.trim();
                    const fresh = tpl.content.getElementById('products-container');
                    if (!fresh) { window.location = url; return; }

                    // Cập nhật tiêu đề + số lượng (giữ nguyên phần còn lại)
                    const title = document.querySelector('.glass-cat-title');
                    const count = document.querySelector('.glass-product-count');
                    if (title && fresh.dataset.title) title.textContent = fresh.dataset.title;
                    if (count && fresh.dataset.count) count.textContent = fresh.dataset.count + ' sản phẩm';

                    container.replaceWith(fresh);
                    fresh.classList.remove('fading');
                    if (push) history.pushState({ url }, '', url);
                })
                .catch(err => {
                    if (reqId !== menuReq) return;
                    console.error(err);
                    window.location = url; // fallback: tải lại cả trang
                });
        }

        // Đổi danh mục
        document.querySelector('.glass-sidebar')?.addEventListener('click', (e) => {
            const item = e.target.closest('.sidebar-cat-item');
            if (!item) return;
            e.preventDefault();
            document.querySelectorAll('.sidebar-cat-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            loadMenu(item.href);
        });

        // Tìm kiếm + phân trang (nằm trong vùng nội dung, dùng ủy quyền sự kiện)
        glassContent?.addEventListener('submit', (e) => {
            const form = e.target.closest('.glass-search-form');
            if (!form) return;
            e.preventDefault();
            const params = new URLSearchParams(new FormData(form)).toString();
            loadMenu(form.action + (params ? '?' + params : ''));
        });

        // Điều hướng bằng nút Back/Forward của trình duyệt
        window.addEventListener('popstate', () => loadMenu(location.href, false));

        // ── Realtime menu ──────────────────────────────────────────
        // Chủ quán thêm sản phẩm / đổi giá / ẩn - hết hàng → khách tự thấy ngay
        // (poll "phiên bản" menu; khi đổi thì làm mới lưới sản phẩm theo view hiện tại).
        (function () {
            var menuVersion = @json($menuVersion);
            var VERSION_URL = '{{ route('menu.version') }}';
            function checkMenuVersion() {
                if (document.hidden) return;
                fetch(VERSION_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) {
                        if (!d || !d.v || d.v === menuVersion) return;
                        menuVersion = d.v;
                        loadMenu(location.href, false);
                    })
                    .catch(function () { });
            }
            setInterval(checkMenuVersion, 20000);
        })();

        // ── Tương tác trong lưới sản phẩm (ủy quyền sự kiện — không cần gắn lại sau khi swap) ──
        glassContent?.addEventListener('click', (e) => {
            // Phân trang
            const pageLink = e.target.closest('.menu-pagination a');
            if (pageLink) {
                e.preventDefault();
                loadMenu(pageLink.href);
                return;
            }

            // Yêu thích (heart)
            const heart = e.target.closest('.menu-card-heart');
            if (heart) {
                e.stopPropagation();
                const productId = heart.getAttribute('data-wishlist');
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
                            showNotice('Đăng nhập để lưu sản phẩm yêu thích. Nếu chưa có tài khoản, hãy đăng ký trước nhé!');
                            return Promise.reject('Unauthorized');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            loadMenu(location.href, false); // làm mới danh sách để server sắp xếp lại
                        }
                    })
                    .catch(err => console.error(err));
                return;
            }

            // Nút "Thêm món"
            const addBtn = e.target.closest('.menu-add-btn');
            if (addBtn) {
                e.stopPropagation();
                const imgEl = addBtn.closest('.menu-card')?.querySelector('.menu-card-img');
                if (typeof window.showGlobalSizeModal === 'function') {
                    window.showGlobalSizeModal(
                        addBtn.dataset.productId,
                        addBtn.dataset.productName,
                        addBtn.dataset.productImg,
                        addBtn.dataset.addUrl,
                        JSON.parse(addBtn.dataset.sizes || '[]'),
                        imgEl,
                        addBtn.dataset.nhietDo
                    );
                }
                return;
            }

            // Bấm vào thẻ sản phẩm → xem chi tiết
            const card = e.target.closest('.menu-card[data-href]');
            if (card) {
                window.location = card.dataset.href;
            }
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
    </script>
@endpush