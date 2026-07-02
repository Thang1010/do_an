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

@include('customer.menu.partials._products')
