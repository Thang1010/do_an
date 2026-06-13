@extends('manager.layout.app')

@section('title', 'Quản lý sản phẩm')
@section('breadcrumb', 'Kinh doanh / <strong>Quản lý sản phẩm</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý sản phẩm</h1>
        <p class="page-subtitle">Danh sách tất cả sản phẩm trong menu</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.products.recipes.excel') }}" class="btn btn-success" style="background-color: #27AE60; border-color: #27AE60; color: white; display: flex; align-items: center; gap: 4px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Xuất công thức (Excel)
        </a>
        <a href="{{ route('manager.products.create') }}" class="btn btn-primary">Thêm sản phẩm</a>
    </div>
</div>

{{-- Filter bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('manager.products.index') }}"
          class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm tên sản phẩm..." value="{{ request('search') }}">
        <select name="danh_muc" class="form-control">
            <option value="">Tất cả danh mục</option>
            @foreach($danhMucs ?? [] as $dm)
                <option value="{{ $dm->id }}" {{ request('danh_muc') == $dm->id ? 'selected' : '' }}>
                    {{ $dm->ten_danh_muc }}
                </option>
            @endforeach
        </select>
        <select name="trang_thai" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="dang_ban" {{ request('trang_thai') == 'dang_ban' ? 'selected' : '' }}>Đang bán</option>
            <option value="ngung_ban" {{ request('trang_thai') == 'ngung_ban' ? 'selected' : '' }}>Ngừng bán</option>
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.products.index') }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

{{-- Products table --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                    <th>Trạng thái</th>
                    <th>Đã bán</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products ?? [] as $i => $product)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <img src="{{ $product->image_url ?? asset('images/default-product.png') }}"
                             alt="{{ $product->ten_san_pham }}" class="img-preview img-preview-sm">
                    </td>
                    <td>
                        <div class="font-600">{{ $product->ten_san_pham }}</div>
                        <div class="text-12 text-muted">{{ Str::limit($product->mo_ta, 50) }}</div>
                    </td>
                    <td>{{ $product->danhMuc->ten_danh_muc ?? '—' }}</td>
                    <td class="price-text">
                        {{ number_format($product->gia, 0, ',', '.') }}đ
                    </td>
                    <td>
                        <label class="toggle-switch">
                            <input type="checkbox" {{ $product->trang_thai === 'dang_ban' ? 'checked' : '' }}
                                   onchange="toggleStatus({{ $product->id }}, this)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="text-12 text-muted ml-6">
                            {{ $product->trang_thai === 'dang_ban' ? 'Đang bán' : 'Ngừng bán' }}
                        </span>
                    </td>
                    <td>{{ $product->so_luong_ban ?? 0 }}</td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.products.edit', $product->id) }}" class="btn btn-secondary btn-sm">Sửa</a>
                            <form method="POST" action="{{ route('manager.products.destroy', $product->id) }}"
                                  onsubmit="return confirmDelete(this, 'Xóa sản phẩm {{ $product->ten_san_pham }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        Chưa có sản phẩm nào. <a href="{{ route('manager.products.create') }}" class="link-primary">Thêm ngay</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($products) && $products->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ $products->total() }} sản phẩm
            </span>
            {{ $products->links() }}
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function toggleStatus(productId, checkbox) {
    const status = checkbox.checked ? 'dang_ban' : 'ngung_ban';
    fetch(`/manager/products/${productId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            || '{{ csrf_token() }}'
        },
        body: JSON.stringify({ trang_thai: status })
    }).then(async response => {
        if (!response.ok) {
            checkbox.checked = !checkbox.checked;
            const data = await response.json();
            if (data.message) {
                alert(data.message);
            }
        }
    }).catch(() => {
        checkbox.checked = !checkbox.checked; // revert on error
    });
}
</script>
@endpush
