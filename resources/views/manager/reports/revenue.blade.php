@extends('layouts.manager')

@section('title', 'Thống kê — Doanh thu')
@section('breadcrumb', 'Báo cáo / <strong>Thống kê doanh thu</strong>')

@section('content')

@php $period = request('period', 'week'); @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Thống kê & Báo cáo</h1>
        <p class="page-subtitle">Phân tích toàn diện hoạt động kinh doanh</p>
    </div>
    <div class="page-actions">
        <select class="form-control w-auto" id="report-period" onchange="changePeriod(this.value)">
            <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hôm nay</option>
            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>7 ngày</option>
            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Tháng này</option>
            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Năm nay</option>
            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
        </select>
        <div id="custom-date-range" class="flex-gap-8 {{ $period === 'custom' ? '' : 'hidden' }}">
            <input type="date" class="form-control w-auto" id="date-from" value="{{ request('from') }}">
            <input type="date" class="form-control w-auto" id="date-to" value="{{ request('to') }}">
            <button class="btn btn-primary" onclick="applyCustomRange()">Áp dụng</button>
        </div>
    </div>
</div>

{{-- Top stat cards --}}
<div class="grid-4 mb-24">
    <div class="stat-card">
        <div class="stat-label">Tổng doanh thu</div>
        <div class="stat-value">{{ number_format($tongDoanhThu ?? 0, 0, ',', '.') }}đ</div>
        <div class="stat-change text-muted">
            {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:78%"></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng đơn hàng</div>
        <div class="stat-value">{{ $tongDon ?? 0 }}</div>
        <div class="stat-change text-muted">
            {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:65%"></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Giá trị đơn trung bình</div>
        <div class="stat-value">{{ number_format($giaTriTrungBinh ?? 0, 0, ',', '.') }}đ</div>
        <div class="stat-change text-muted">
            {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:55%"></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Điểm thưởng đã cấp</div>
        <div class="stat-value">{{ number_format($diemDaCap ?? 0, 0, ',', '.') }}</div>
        <div class="stat-change text-muted">
            Đã dùng: {{ number_format($diemDaDung ?? 0, 0, ',', '.') }} điểm
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:44%"></div></div>
    </div>
</div>

{{-- Report Tabs --}}
<div class="tab-card">
    <div class="tab-list tab-list-inner">
        <button class="tab-btn active" onclick="switchTab('tab-revenue', this)">Doanh thu</button>
        <button class="tab-btn" onclick="switchTab('tab-orders', this)">Đơn hàng</button>
        <button class="tab-btn" onclick="switchTab('tab-products', this)">Sản phẩm bán chạy</button>
        <button class="tab-btn" onclick="switchTab('tab-hours', this)">Khung giờ đông khách</button>
        <button class="tab-btn" onclick="switchTab('tab-staff', this)">Hiệu suất nhân viên</button>
        <button class="tab-btn" onclick="switchTab('tab-inventory', this)">Tồn kho</button>
        <button class="tab-btn" onclick="switchTab('tab-points', this)">Điểm thưởng</button>
    </div>

    {{-- Tab: Doanh thu --}}
    <div class="tab-panel active p-24" id="tab-revenue">
        <div class="layout-2fr-1fr">
            <div>
                <div class="form-label mb-12">Doanh thu theo ngày</div>
                {{-- Bar chart --}}
                <div class="chart-bar-container-180">
                    @php $revMax = $revenueByDay->max('tong') ?: 1; @endphp
                    @if($revenueByDay->count() > 0)
                        @foreach($revenueByDay as $day)
                        <div class="chart-bar-item-sm">
                            <div style="
                                width:100%;height:{{ round($day->tong / $revMax * 140) }}px;
                                background:{{ $day->tong == $revMax ? '#30261C' : '#E2D9C8' }};
                                border-radius:4px 4px 0 0;
                                transition:height 0.4s;
                            " title="{{ number_format($day->tong, 0, ',', '.') }}đ"></div>
                            <span class="chart-date-label">{{ \Carbon\Carbon::parse($day->ngay)->format('d/m') }}</span>
                        </div>
                        @endforeach
                    @else
                        <div class="text-muted text-12">Chưa có dữ liệu doanh thu.</div>
                    @endif
                </div>
                <div class="chart-footer">
                    <span>Đơn vị: đồng</span>
                    <span>{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="flex-col-14">
                @php
                    $maxRevenueDay = $revenueByDay->sortByDesc('tong')->first();
                    $minRevenueDay = $revenueByDay->sortBy('tong')->first();
                    $avgRevenueDay = $revenueByDay->count() ? round($revenueByDay->avg('tong')) : 0;
                @endphp
                <div class="info-box">
                    <div class="stat-label">Cao nhất / ngày</div>
                    <div class="stat-value-large">
                        {{ number_format($maxRevenueDay->tong ?? 0, 0, ',', '.') }}đ
                    </div>
                    <div class="text-12 text-muted">
                        {{ $maxRevenueDay ? \Carbon\Carbon::parse($maxRevenueDay->ngay)->format('d/m/Y') : '—' }}
                    </div>
                </div>
                <div class="info-box">
                    <div class="stat-label">Trung bình / ngày</div>
                    <div class="stat-value-large">
                        {{ number_format($avgRevenueDay, 0, ',', '.') }}đ
                    </div>
                </div>
                <div class="info-box">
                    <div class="stat-label">Thấp nhất / ngày</div>
                    <div class="stat-value-large">
                        {{ number_format($minRevenueDay->tong ?? 0, 0, ',', '.') }}đ
                    </div>
                    <div class="text-12 text-muted">
                        {{ $minRevenueDay ? \Carbon\Carbon::parse($minRevenueDay->ngay)->format('d/m/Y') : '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab: Đơn hàng --}}
    <div class="tab-panel p-24" id="tab-orders">
        <div class="grid-3 mb-20">
            @php
                $orderStatuses = [
                    ['key' => 'chờ xác nhận', 'alt' => 'cho_xac_nhan', 'label' => 'Chờ xác nhận', 'badge' => 'badge-pending'],
                    ['key' => 'đã xác nhận', 'alt' => 'dang_pha_che', 'label' => 'Đã xác nhận', 'badge' => 'badge-done'],
                    ['key' => 'đã hủy', 'alt' => 'huy', 'label' => 'Đã hủy', 'badge' => 'badge-cancelled'],
                ];
            @endphp
            @foreach($orderStatuses as $s)
            @php $count = $statusCounts[$s['key']] ?? $statusCounts[$s['alt']] ?? 0; @endphp
            <div class="status-count-card">
                <span class="status-count-label">{{ $s['label'] }}</span>
                <span class="badge {{ $s['badge'] }} badge-lg">
                    {{ $count }}
                </span>
            </div>
            @endforeach
        </div>
        <div class="chart-placeholder" style="height:160px;">Biểu đồ tròn — phân bố trạng thái đơn hàng</div>
    </div>

    {{-- Tab: Sản phẩm bán chạy --}}
    <div class="tab-panel p-24" id="tab-products">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Hạng</th><th>Sản phẩm</th><th>Danh mục</th>
                    <th>Số lượng bán</th><th>Doanh thu</th><th>Tỷ lệ</th>
                </tr></thead>
                <tbody>
                    @forelse($topProducts as $i => $p)
                    @php $percent = $maxSold > 0 ? round($p->tong_so_luong / $maxSold * 100) : 0; @endphp
                    <tr>
                        <td><strong>#{{ $i+1 }}</strong></td>
                        <td><strong>{{ $p->ten_san_pham }}</strong></td>
                        <td>{{ $p->ten_danh_muc }}</td>
                        <td>{{ $p->tong_so_luong }} ly</td>
                        <td><strong>{{ number_format($p->tong_doanh_thu, 0, ',', '.') }}đ</strong></td>
                        <td>
                            <div class="progress-bar w-160">
                                <div class="progress-fill" style="width:{{ $percent }}%;"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-state-sm">
                            Chưa có dữ liệu sản phẩm.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Khung giờ đông khách --}}
    <div class="tab-panel p-24" id="tab-hours">
        <div class="form-label mb-16">Phân bố đơn hàng theo giờ</div>
        <div class="chart-bar-container">
            @php $hours = range(6, 21); @endphp
            @foreach($hours as $h)
            @php
                $count = $peakHours[$h] ?? 0;
                $ratio = $maxHour > 0 ? $count / $maxHour : 0;
                $height = round($ratio * 120);
                $color = $ratio >= 0.7 ? '#30261C' : ($ratio >= 0.4 ? '#7a6555' : '#E2D9C8');
            @endphp
            <div class="chart-bar-item-sm">
                <div style="
                    width:100%;height:{{ $height }}px;
                    background:{{ $color }};
                    border-radius:4px 4px 0 0;
                " title="{{ $count }} đơn"></div>
                <span class="chart-hour-label">{{ $h }}h</span>
            </div>
            @endforeach
        </div>
        <div class="chart-legend">
            <span class="chart-legend-item"><span class="chart-legend-dot" style="background:#30261C;"></span> Rất đông (≥70%)</span>
            <span class="chart-legend-item"><span class="chart-legend-dot" style="background:#7a6555;"></span> Đông (40–69%)</span>
            <span class="chart-legend-item"><span class="chart-legend-dot" style="background:#E2D9C8;"></span> Bình thường (&lt;40%)</span>
        </div>
    </div>

    {{-- Tab: Hiệu suất nhân viên --}}
    <div class="tab-panel p-24" id="tab-staff">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Nhân viên</th><th>Đơn xử lý</th>
                    <th>Doanh thu</th><th>TB/đơn</th>
                </tr></thead>
                <tbody>
                    @forelse($staffPerformance as $s)
                    @php $avgOrder = $s->so_don > 0 ? round($s->tong_doanh_thu / $s->so_don) : 0; @endphp
                    <tr>
                        <td><strong>{{ $s->ho_ten }}</strong></td>
                        <td>{{ $s->so_don }} đơn</td>
                        <td><strong>{{ number_format($s->tong_doanh_thu, 0, ',', '.') }}đ</strong></td>
                        <td>{{ number_format($avgOrder, 0, ',', '.') }}đ</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty-state-sm">
                            Chưa có dữ liệu nhân viên.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Tồn kho --}}
    <div class="tab-panel p-24" id="tab-inventory">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Nguyên liệu</th><th>Tồn kho</th>
                    <th>Tối thiểu</th><th>Trạng thái</th>
                </tr></thead>
                <tbody>
                    @forelse($inventoryReport as $item)
                    <tr>
                        <td><strong>{{ $item->ten_nguyen_lieu }}</strong></td>
                        <td class="{{ $item->so_luong_ton <= $item->muc_canh_bao ? 'low-stock' : '' }}">
                            {{ $item->so_luong_ton }} {{ $item->don_vi_tinh }}
                        </td>
                        <td>{{ $item->muc_canh_bao }} {{ $item->don_vi_tinh }}</td>
                        <td>
                            @if($item->so_luong_ton <= 0)
                                <span class="badge badge-inactive">Hết hàng</span>
                            @elseif($item->so_luong_ton <= $item->muc_canh_bao)
                                <span class="badge badge-pending">Sắp hết</span>
                            @else
                                <span class="badge badge-active">Đủ hàng</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty-state-sm">
                            Chưa có dữ liệu tồn kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Điểm thưởng --}}
    <div class="tab-panel p-24" id="tab-points">
        <div class="grid-3 mb-20">
            @php $totalDiemConLai = ($totalDiemCap ?? 0) - ($totalDiemDung ?? 0); @endphp
            <div class="info-box-lg">
                <div class="stat-label">Tổng điểm đã cấp</div>
                <div class="stat-value-xl">
                    {{ number_format($totalDiemCap ?? 0, 0, ',', '.') }}
                </div>
            </div>
            <div class="info-box-lg">
                <div class="stat-label">Tổng điểm đã dùng</div>
                <div class="stat-value-xl">
                    {{ number_format($totalDiemDung ?? 0, 0, ',', '.') }}
                </div>
            </div>
            <div class="info-box-lg">
                <div class="stat-label">Tổng điểm còn lại</div>
                <div class="stat-value-xl">
                    {{ number_format($totalDiemConLai, 0, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="chart-placeholder" style="height:160px;">Biểu đồ điểm thưởng theo tháng</div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function changePeriod(val) {
    const customRange = document.getElementById('custom-date-range');
    if (val === 'custom') {
        customRange.classList.remove('hidden');
    } else {
        customRange.classList.add('hidden');
    }
}
function applyCustomRange() {
    const from = document.getElementById('date-from').value;
    const to   = document.getElementById('date-to').value;
    if (from && to) {
        window.location.href = `?period=custom&from=${from}&to=${to}`;
    }
}
</script>
@endpush
