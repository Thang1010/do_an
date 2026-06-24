@extends('manager.layout.app')

@section('title', 'Bảng điều khiển')
@section('breadcrumb', '<strong>Bảng điều khiển</strong>')

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div id="dashboard-stats-wrap">
    @include('manager.dashboard.partials.stat-cards')
</div>

{{-- ===== MAIN GRID ===== --}}
<div class="layout-main-sidebar mb-20">

    {{-- Biểu đồ doanh thu trong tuần --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Doanh thu ngày {{ now()->format('d/m/Y') }}</span>
        </div>
        <div class="card-body">
            @if($doanhThu7Ngay->sum('total') == 0)
                <div class="empty-state">
                    Chưa có dữ liệu doanh thu trong tuần này.
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
                        data-color="{{ $day['is_today'] ? '#30261C' : '#E2D9C8' }}"
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
            @forelse($latestOrders as $order)
            <div class="order-item">
                <div>
                    <div class="order-item-title">
                        #{{ $order->id }} —
                        {{ $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? 'Khách vãng lai' }}
                    </div>
                    <div class="order-item-sub">
                        {{ number_format($order->tong_tien, 0, ',', '.') }}đ
                        · {{ $order->created_at->diffForHumans() }}
                    </div>
                </div>
                <span class="badge {{ $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'badge-done' : 'badge-pending' }}">
                    {{ $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'Đã TT' : 'Chưa TT' }}
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

    {{-- Cảnh báo tồn kho --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Cảnh báo tồn kho</span>
            <a href="{{ route('manager.inventory.index') }}" class="btn btn-secondary btn-sm">Quản lý kho</a>
        </div>
        <div class="card-body py-12">
            @forelse($dsNguyenLieuSapHet as $nl)
            @php
                $maxTieuHao = (float) ($nl->max_tieu_hao ?? 0);
                $divisor = $maxTieuHao > 0 ? $maxTieuHao : 1;
                $soCup = (int) floor(((float) $nl->so_luong) / $divisor);
                $isHet = $soCup <= 0;
            @endphp
            <div class="stock-item {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="flex-1">
                    <div class="stock-item-name">
                        {{ $nl->ten_nguyen_lieu }}
                        @if($isHet)
                            <span class="badge" style="background-color: #dc3545; color: white; padding: 2px 6px; font-size: 10px; margin-left: 5px;">Hết hàng</span>
                        @else
                            <span class="badge" style="background-color: #ffc107; color: #212529; padding: 2px 6px; font-size: 10px; margin-left: 5px;">Sắp hết</span>
                        @endif
                    </div>
                    <div class="stock-item-detail">
                        Còn: <span class="{{ $isHet ? 'text-danger font-600' : 'text-warning font-600' }}">{{ number_format($nl->so_luong, 2, ',', '.') }} {{ $nl->don_vi_tinh }}</span>
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
// ── Polling thẻ thống kê: doanh thu/đơn hàng hôm nay tự cập nhật, không cần F5 ──
(function () {
    var wrap = document.getElementById('dashboard-stats-wrap');
    if (!wrap) return;
    var INTERVAL = 25000; // 25 giây
    var inFlight = false;
    var POLL_URL = '{{ route('manager.dashboard.stats-poll') }}';

    function applyDynamicStyles(root) {
        root.querySelectorAll('.js-dynamic-width').forEach(function (el) {
            var width = Math.max(0, Math.min(100, Number(el.dataset.width || 0)));
            el.style.width = width + '%';
        });
        root.querySelectorAll('.js-dynamic-color').forEach(function (el) {
            if (el.dataset.color) el.style.color = el.dataset.color;
        });
    }

    function refresh() {
        if (inFlight || document.hidden) return;
        inFlight = true;
        fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && typeof data.html === 'string') {
                    wrap.innerHTML = data.html;
                    applyDynamicStyles(wrap);
                }
            })
            .catch(function () { /* im lặng */ })
            .finally(function () { inFlight = false; });
    }

    setInterval(refresh, INTERVAL);
})();

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

