@php
    $panelTitle = $search !== ''
        ? 'Tìm kiếm: "' . $search . '"'
        : ($activeCategory ? $activeCategory->ten_danh_muc : 'Tất cả món');
@endphp
<!-- Lưới sản phẩm -->
<div id="products-container" data-title="{{ $panelTitle }}" data-count="{{ $products->total() }}">
    <div class="products-grid">
        @forelse($products as $product)
            <div class="menu-card cursor-pointer" data-href="{{ route('menu.show', $product->id) }}">
                <!-- Image -->
                <div class="menu-card-img-wrap">
                    <img src="{{ $product->image_url }}" alt="{{ $product->ten_san_pham }}" class="menu-card-img"
                        loading="lazy" />
                    @if($product->noi_bat)
                        <span class="menu-card-feature-badge">⭐ Nổi bật</span>
                    @endif
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
                    @if($product->mo_ta)
                        <p class="menu-card-desc" style="font-size: 11px; color: rgba(30,17,6,0.65); margin: 2px 0 0 0; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: auto;">
                            {{ $product->mo_ta }}
                        </p>
                    @endif
                    @php $prodRating = round($product->avg_rating ?? 0); @endphp
                    <div style="display:flex; gap:2px; margin-top: 3px; align-items: center;">
                        @for($i = 1; $i <= 5; $i++)
                            <svg width="12" height="12" viewBox="0 0 20 20"
                                 fill="{{ $i <= $prodRating ? '#f59e0b' : 'rgba(30,17,6,0.18)' }}">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                        @if(($product->danh_gia_san_pham_count ?? 0) > 0)
                            <span style="font-size: 10px; color: rgba(30,17,6,0.5); font-family:'Outfit',sans-serif; margin-left: 3px; line-height: 1;">({{ $product->danh_gia_san_pham_count }})</span>
                        @endif
                    </div>
                    <div class="menu-card-footer">
                        <span class="menu-card-price">
                            {{ number_format($product->gia_khuyen_mai ?? $product->gia_goc, 0, ',', '.') }}đ
                        </span>
                        @php
                            $basePrice = (float)($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);
                            $sizesJson = $product->kichCo->map(function($kc) use ($basePrice) {
                                return [
                                    'id' => $kc->id,
                                    'name' => $kc->ten_kich_co,
                                    'price' => $basePrice * (float)($kc->he_so_gia ?? 1),
                                    'code' => $kc->ma_kich_co ?? '',
                                ];
                            })->toJson();
                        @endphp
                        <button class="menu-card-btn menu-add-btn"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->ten_san_pham }}"
                            data-product-img="{{ $product->image_url }}"
                            data-add-url="{{ route('cart.add') }}"
                            data-sizes="{{ $sizesJson }}"
                            data-nhiet-do="{{ $product->nhiet_do ?? '' }}">Thêm
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
