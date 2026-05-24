@extends('manager.layout.app')

@section('title', 'Bảng điều khiển')
@section('breadcrumb', '<strong>Bảng điều khiển</strong>')

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="grid-4 mb-24">
    <div class="stat-card">
        <div class="stat-label">Doanh thu hôm nay</div>
        <div class="stat-value">{{ number_format($doanhThuHomNay, 0, ',', '.') }}đ</div>
        @if($doanhThuHomQua > 0)
            @php $pctRev = round(($doanhThuHomNay - $doanhThuHomQua) / $doanhThuHomQua * 100, 1) @endphp
            <div class="stat-change {{ $pctRev >= 0 ? 'up' : 'down' }}">
                {{ $pctRev >= 0 ? '+' : '' }}{{ $pctRev }}% so với hôm qua
            </div>
        @else
            <div class="stat-change text-muted">Chưa có dữ liệu hôm qua</div>
        @endif
        <div class="stat-bar">
            @php $width = $doanhThuHomQua > 0 ? min(100, round($doanhThuHomNay/$doanhThuHomQua*70)) : 50; @endphp
            <div class="stat-bar-fill js-dynamic-width" data-width="{{ (int) $width }}"></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Đơn hàng hôm nay</div>
        <div class="stat-value">{{ $donHangHomNay }}</div>
        @php $diffDon = $donHangHomNay - $donHangHomQua @endphp
        <div class="stat-change {{ $diffDon >= 0 ? 'up' : 'down' }}">
            {{ $diffDon >= 0 ? '+' : '' }}{{ $diffDon }} đơn so với hôm qua
        </div>
        <div class="stat-bar">
            @php $width = $donHangHomQua > 0 ? min(100, round($donHangHomNay/$donHangHomQua*70)) : 50; @endphp
            <div class="stat-bar-fill js-dynamic-width" data-width="{{ (int) $width }}"></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Khách hàng mới</div>
        <div class="stat-value">{{ $khachHangMoi }}</div>
        <div class="stat-change text-muted">Đăng ký hôm nay</div>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:35%"></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nguyên liệu sắp hết</div>
        <div class="stat-value {{ $nguyenLieuSapHet > 0 ? 'text-danger' : '' }}">
            {{ $nguyenLieuSapHet }}
        </div>
        <div class="stat-change js-dynamic-color" data-color="{{ $nguyenLieuSapHet > 0 ? '#92400E' : 'var(--text-muted)' }}">
            {{ $nguyenLieuSapHet > 0 ? 'Cần nhập kho ngay' : 'Tồn kho ổn định' }}
        </div>
        <div class="stat-bar">
            @php $width = $nguyenLieuSapHet > 0 ? '20' : '0'; @endphp
            <div class="stat-bar-fill stat-bar-fill-danger js-dynamic-width" data-width="{{ (int) $width }}"></div>
        </div>
    </div>
</div>

{{-- ===== MAIN GRID ===== --}}
<div class="layout-main-sidebar mb-20">

    {{-- Biểu đồ doanh thu 7 ngày --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Doanh thu 7 ngày gần nhất</span>
            <div class="flex-gap-8">
                <a href="?period=week" class="btn btn-secondary btn-sm {{ request('period','week')==='week' ? '' : '' }}">7 ngày</a>
                <a href="?period=month" class="btn btn-secondary btn-sm">30 ngày</a>
                <a href="?period=year" class="btn btn-secondary btn-sm">Năm</a>
            </div>
        </div>
        <div class="card-body">
            @if($doanhThu7Ngay->sum('total') == 0)
                <div class="empty-state">
                    Chưa có dữ liệu doanh thu trong 7 ngày qua.
                </div>
            @else
            <div class="chart-bar-container">
                @foreach($doanhThu7Ngay as $day)
                @php $h = $maxDoanhThu > 0 ? round($day['total'] / $maxDoanhThu * 130) : 4; @endphp
                <div class="chart-bar-item">
                    <span class="chart-label">
                        {{ $day['total'] > 0 ? number_format($day['total']/1000, 0) . 'K' : '' }}
                    </span>
                    <div
                        class="dashboard-chart-bar"
                        data-height="{{ max(4, $h) }}"
                        data-color="{{ $day['total'] == $maxDoanhThu && $maxDoanhThu > 0 ? '#30261C' : '#E2D9C8' }}"
                        title="{{ number_format($day['total'],0,',','.') }}đ"
                    ></div>
                    <span class="chart-day-label">{{ $day['thu'] }}</span>
                    <span class="chart-date-label">{{ $day['ngay'] }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Đơn hàng mới nhất --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Đơn hàng mới</span>
            <a href="{{ route('manager.orders.index') }}" class="btn btn-secondary btn-sm">Xem tất cả</a>
        </div>
        <div class="overflow-hidden">
            @php
                $statuses = [
                    'cho_xac_nhan' => ['label'=>'Chờ xác nhận','class'=>'badge-pending'],
                    'chờ xác nhận' => ['label'=>'Chờ xác nhận','class'=>'badge-pending'],
                    'dang_pha_che' => ['label'=>'Đã xác nhận','class'=>'badge-done'],
                    'hoan_thanh'   => ['label'=>'Đã xác nhận','class'=>'badge-done'],
                    'da_giao'      => ['label'=>'Đã xác nhận','class'=>'badge-done'],
                    'đã xác nhận'  => ['label'=>'Đã xác nhận','class'=>'badge-done'],
                    'huy'          => ['label'=>'Đã hủy','class'=>'badge-cancelled'],
                    'đã hủy'       => ['label'=>'Đã hủy','class'=>'badge-cancelled'],
                ];
            @endphp
            @forelse($latestOrders as $order)
            <div class="order-item">
                <div>
                    <div class="order-item-title">
                        #{{ $order->id }} —
                        {{ $order->nguoiDung->ho_ten ?? $order->ten_khach_hang ?? 'Khách vãng lai' }}
                    </div>
                    <div class="order-item-sub">
                        {{ number_format($order->tong_tien, 0, ',', '.') }}đ
                        · {{ $order->created_at->diffForHumans() }}
                    </div>
                </div>
                <span class="badge {{ $statuses[$order->trang_thai_don]['class'] ?? 'badge-default' }}">
                    {{ $statuses[$order->trang_thai_don]['label'] ?? $order->trang_thai_don }}
                </span>
            </div>
            @empty
            <div class="empty-state-32">
                Chưa có đơn hàng nào hôm nay.
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== BOTTOM GRID ===== --}}
<div class="layout-half">

    {{-- Sản phẩm bán chạy --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Sản phẩm bán chạy tuần này</span>
            <a href="{{ route('manager.reports.products') }}" class="btn btn-secondary btn-sm">Chi tiết</a>
        </div>
        <div class="card-body py-12">
            @forelse($topProducts as $i => $prod)
            <div class="top-product-item {{ !$loop->last ? 'border-bottom' : '' }}">
                <span class="top-product-rank">{{ $i+1 }}</span>
                <div class="flex-1">
                    <div class="top-product-name">{{ $prod->ten_san_pham }}</div>
                    <div class="progress-bar mt-6">
                        <div class="progress-fill js-dynamic-width" data-width="{{ $maxSold > 0 ? round($prod->tong_so_luong / $maxSold * 100) : 0 }}"></div>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="top-product-qty">{{ number_format($prod->tong_so_luong, 0, ',', '.') }} ly</div>
                    <div class="top-product-rev">{{ number_format($prod->tong_doanh_thu, 0, ',', '.') }}đ</div>
                </div>
            </div>
            @empty
            <div class="empty-state-32">Chưa có dữ liệu bán hàng tuần này.</div>
            @endforelse
        </div>
    </div>

    {{-- Nguyên liệu sắp hết --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Cảnh báo tồn kho</span>
            <a href="{{ route('manager.inventory.index') }}" class="btn btn-secondary btn-sm">Quản lý kho</a>
        </div>
        <div class="card-body py-12">
            @forelse($dsNguyenLieuSapHet as $nl)
            <div class="stock-item {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="flex-1">
                    <div class="stock-item-name">{{ $nl->ten_nguyen_lieu }}</div>
                    <div class="stock-item-detail">
                        Còn: <span class="low-stock">{{ $nl->so_luong }} {{ $nl->don_vi_tinh }}</span>
                    </div>
                </div>
                <a href="{{ route('manager.inventory.import') }}" class="btn btn-warning btn-sm">Nhập thêm</a>
            </div>
            @empty
            <div class="empty-state-32" style="color:var(--success);">
                Tất cả nguyên liệu đang ở mức an toàn.
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.dashboard-chart-bar {
    width: 100%;
    min-height: 4px;
    height: 4px;
    background: #E2D9C8;
    border-radius: 6px 6px 0 0;
    transition: height 0.4s;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-dynamic-width').forEach(function (el) {
        var width = Number(el.dataset.width || 0);
        width = Math.max(0, Math.min(100, width));
        el.style.width = width + '%';
    });

    document.querySelectorAll('.js-dynamic-color').forEach(function (el) {
        var color = el.dataset.color;
        if (color) {
            el.style.color = color;
        }
    });

    document.querySelectorAll('.dashboard-chart-bar').forEach(function (el) {
        var height = Number(el.dataset.height || 4);
        var bgColor = el.dataset.color;

        el.style.height = Math.max(4, height) + 'px';
        if (bgColor) {
            el.style.background = bgColor;
        }
    });
});
</script>
@endpush

