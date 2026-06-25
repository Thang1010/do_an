{{-- Thanh phân trang dùng chung cho danh sách quản lý.
     LUÔN hiển thị (kể cả khi chỉ có 1 trang); nút Trước/Sau tự khóa khi không
     còn trang để chuyển.
     Tham số:
       - $paginator   : đối tượng LengthAwarePaginator (bắt buộc)
       - $label       : đơn vị hiển thị sau tổng số, vd "bàn ăn" (tùy chọn)
       - $footerClass : class bổ sung cho .card-footer (tùy chọn) --}}
@php($p = $paginator ?? null)
@if($p && method_exists($p, 'total'))
<div class="card-footer {{ $footerClass ?? '' }}">
    <div class="pagination-footer">
        <span class="pagination-info">
            @if($p->total() > 0)
                Hiển thị {{ $p->firstItem() }}–{{ $p->lastItem() }} / {{ $p->total() }} {{ $label ?? '' }}
            @else
                Hiển thị 0 / 0 {{ $label ?? '' }}
            @endif
        </span>
        <div class="pager-nav">
            @if($p->onFirstPage())
                <span class="pager-btn disabled">« Trước</span>
            @else
                <a class="pager-btn" href="{{ $p->previousPageUrl() }}">« Trước</a>
            @endif
            <span class="pager-page">{{ $p->currentPage() }} / {{ max($p->lastPage(), 1) }}</span>
            @if($p->hasMorePages())
                <a class="pager-btn" href="{{ $p->nextPageUrl() }}">Sau »</a>
            @else
                <span class="pager-btn disabled">Sau »</span>
            @endif
        </div>
    </div>
</div>
@endif
