@extends('manager.layout.app')

@section('title', 'Chi tiết danh mục')
@section('breadcrumb', 'Kinh doanh / Danh mục / <strong>' . $category->ten_danh_muc . '</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Chi tiết danh mục: {{ $category->ten_danh_muc }}</h1>
        <p class="page-subtitle">Danh sách các sản phẩm thuộc danh mục {{ $category->ten_danh_muc }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.categories.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

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
                    <td>{{ $products->firstItem() + $i }}</td>
                    <td>
                        <img src="{{ $product->image_url ?? asset('images/default-product.png') }}"
                             alt="{{ $product->ten_san_pham }}" class="img-preview img-preview-sm">
                    </td>
                    <td>
                        <div class="font-600">{{ $product->ten_san_pham }}</div>
                        <div class="text-12 text-muted">{{ Str::limit($product->mo_ta, 50) }}</div>
                    </td>
                    <td>{{ $product->danhMuc->ten_danh_muc ?? $category->ten_danh_muc }}</td>
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
                    <td colspan="7" class="empty-state">
                        Chưa có sản phẩm nào trong danh mục này. <a href="{{ route('manager.products.create') }}" class="link-primary">Thêm ngay</a>
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
    }).catch(() => {
        checkbox.checked = !checkbox.checked; // revert on error
    });
}
</script>
@endpush
