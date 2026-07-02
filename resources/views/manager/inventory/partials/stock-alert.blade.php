{{-- Dải cảnh báo tồn kho (tự cập nhật qua polling). Cần: $hetCount, $lowCount. --}}
@if(($hetCount ?? 0) > 0 || ($lowCount ?? 0) > 0)
<div class="alert alert-warning alert-row {{ $hetCount > 0 ? 'alert-danger-strong' : '' }}">
    <span>
        @if($hetCount > 0)
            Có <strong>{{ $hetCount }}</strong> nguyên liệu <strong>đã hết hàng</strong> (tồn kho = 0).
        @endif
        @if($lowCount > 0)
            Có <strong>{{ $lowCount }}</strong> nguyên liệu <strong>sắp hết</strong> (chỉ còn đủ làm ≤ 20 cốc/sản phẩm).
        @endif
        — cần nhập kho ngay!
    </span>
    <button onclick="document.getElementById('low-stock-section').scrollIntoView({behavior:'smooth'})"
            class="btn {{ $hetCount > 0 ? 'btn-danger' : 'btn-warning' }} btn-sm">Xem ngay</button>
</div>
@endif
