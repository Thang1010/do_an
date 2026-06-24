{{-- 4 thẻ thống kê đầu trang (tự cập nhật qua polling). --}}
{{-- Cần: $doanhThuHomNay, $doanhThuHomQua, $donHangHomNay, $donHangHomQua, $khachHangMoi, $nguyenLieuSapHet --}}
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
