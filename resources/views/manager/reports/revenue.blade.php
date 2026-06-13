@extends('manager.layout.app')

@section('title', 'Thống kê — Doanh thu')
@section('breadcrumb', 'Báo cáo / <strong>Thống kê doanh thu</strong>')

@section('content')

@php $period = request('period', 'today'); @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Thống kê & Báo cáo</h1>
        <p class="page-subtitle">Phân tích toàn diện hoạt động kinh doanh</p>
    </div>
    <div class="page-actions">
        <form method="GET" action="{{ route('manager.reports.revenue') }}" class="flex-gap-8" style="align-items: center; display: flex;">
            <input type="date" class="form-control w-auto" name="from" value="{{ request('from') ?? $from->format('Y-m-d') }}" required>
            <span>-</span>
            <input type="date" class="form-control w-auto" name="to" value="{{ request('to') ?? $to->format('Y-m-d') }}" required>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('manager.reports.revenue') }}" class="btn btn-secondary">Xóa lọc</a>
            <a href="{{ route('manager.reports.revenue.export', ['from' => request('from') ?? $from->format('Y-m-d'), 'to' => request('to') ?? $to->format('Y-m-d')]) }}" class="btn btn-success" style="background-color: #27AE60; border-color: #27AE60; color: white; display: flex; align-items: center; gap: 4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Xuất Excel
            </a>
        </form>
    </div>
</div>

{{-- Top stat cards --}}
<div class="grid-3 mb-24">
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
</div>

{{-- Report Tabs --}}
<div class="tab-card">
    <div class="tab-list tab-list-inner" style="display: none;">
        <button class="tab-btn active" onclick="switchTab('tab-revenue-orders', this)">Doanh thu & Đơn hàng</button>
    </div>

    {{-- Tab: Doanh thu & Đơn hàng --}}
    <div class="tab-panel active p-24" id="tab-revenue-orders">
        <div class="layout-2fr-1fr">
            <div>
                <div class="form-label mb-12">Biểu đồ Doanh thu & Số lượng đơn hàng</div>
                <div style="width: 100%; height: 350px;">
                    <canvas id="revenueOrdersChart"></canvas>
                </div>
                <div class="mt-16">
                    <div class="form-label mb-8">Ghi chú:</div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 24px; height: 2px; border-top: 2px dashed #95A5A6; display: inline-block;"></span>
                            <span class="text-13 text-muted">Đường trung bình/ngày</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 24px; height: 2px; background-color: #2C3E50; display: inline-block;"></span>
                            <span class="text-13 text-muted">Đường biểu diễn số lượng đơn</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 12px; height: 12px; border-radius: 50%; background-color: #2ECC71; display: inline-block; margin-left: 6px; margin-right: 6px;"></span>
                            <span class="text-13 text-muted">Cột biểu diễn ngày có doanh thu cao nhất</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 12px; height: 12px; border-radius: 50%; background-color: #E74C3C; display: inline-block; margin-left: 6px; margin-right: 6px;"></span>
                            <span class="text-13 text-muted">Cột biểu diễn ngày có doanh thu thấp nhất</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 12px; height: 12px; border-radius: 50%; background-color: #F1C40F; display: inline-block; margin-left: 6px; margin-right: 6px;"></span>
                            <span class="text-13 text-muted">Cột biểu diễn các ngày có doanh thu bình thường</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-col-14">
                @php
                    $maxRevenueDay = $revenueByDay->sortByDesc('tong')->first();
                    $avgRevenueDay = $revenueByDay->count() ? round($revenueByDay->avg('tong')) : 0;
                    $lastDay = $revenueByDay->last();
                    
                    $hieuSuat = "Chưa có dữ liệu";
                    $hieuSuatColor = "text-muted";
                    if ($lastDay && $avgRevenueDay > 0) {
                        if ($lastDay->tong >= $avgRevenueDay * 1.2) {
                            $hieuSuat = "Rất tốt (Tăng trưởng mạnh)";
                            $hieuSuatColor = "text-success";
                        } elseif ($lastDay->tong >= $avgRevenueDay) {
                            $hieuSuat = "Tốt (Đang tăng)";
                            $hieuSuatColor = "text-success";
                        } elseif ($lastDay->tong >= $avgRevenueDay * 0.8) {
                            $hieuSuat = "Ổn định";
                            $hieuSuatColor = "text-warning";
                        } else {
                            $hieuSuat = "Giảm (Cần chú ý)";
                            $hieuSuatColor = "text-danger";
                        }
                    }
                    
                    $orderStatuses = [
                        ['key' => 'đã thanh toán', 'label' => 'Đã thanh toán', 'badge' => 'badge-done'],
                        ['key' => 'chưa thanh toán', 'label' => 'Chưa thanh toán', 'badge' => 'badge-warning'],
                    ];
                @endphp
                <div class="info-box" style="border-left: 4px solid #3498db;">
                    <div class="stat-label">Đánh giá hiệu suất</div>
                    <div class="stat-value-large {{ $hieuSuatColor }}" style="font-size: 16px;">
                        {{ $hieuSuat }}
                    </div>
                </div>
                <div class="info-box">
                    <div class="stat-label">Trung bình / ngày</div>
                    <div class="stat-value-large">
                        {{ number_format($avgRevenueDay, 0, ',', '.') }}đ
                    </div>
                </div>
                <div class="info-box">
                    <div class="stat-label">Cao nhất / ngày</div>
                    <div class="stat-value-large">
                        {{ number_format($maxRevenueDay->tong ?? 0, 0, ',', '.') }}đ
                    </div>
                    <div class="text-12 text-muted">
                        {{ $maxRevenueDay ? \Carbon\Carbon::parse($maxRevenueDay->ngay)->format('d/m/Y') : '—' }}
                    </div>
                </div>
                @foreach($orderStatuses as $s)
                @php $count = $statusCounts[$s['key']] ?? 0; @endphp
                <div class="info-box">
                    <div class="stat-label">{{ $s['label'] }}</div>
                    <div class="stat-value-large">
                        {{ $count }} <span class="text-12 text-muted">đơn</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>



</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function changePeriod(val) {
    const customRange = document.getElementById('custom-date-range');
    if (val === 'custom') {
        customRange.classList.remove('hidden');
    } else {
        customRange.classList.add('hidden');
        window.location.href = '?period=' + val;
    }
}
function applyCustomRange() {
    const from = document.getElementById('date-from').value;
    const to   = document.getElementById('date-to').value;
    if (from && to) {
        window.location.href = `?period=custom&from=${from}&to=${to}`;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bar-height]').forEach(function (el) {
        var rawHeight = Number(el.dataset.barHeight || 0);
        var height = Math.max(0, rawHeight);
        var color = el.dataset.barColor || '#E2D9C8';

        el.style.width = '100%';
        el.style.height = height + 'px';
        el.style.background = color;
        el.style.borderRadius = '4px 4px 0 0';
        el.style.transition = 'height 0.4s';
    });

    document.querySelectorAll('[data-progress-width]').forEach(function (el) {
        var rawWidth = Number(el.dataset.progressWidth || 0);
        var width = Math.max(0, Math.min(100, rawWidth));
        el.style.width = width + '%';
    });

    const chartData = {!! json_encode($revenueByDay->map(function($item) {
        return [
            'date' => \Carbon\Carbon::parse($item->ngay)->format('d/m'),
            'revenue' => (float) $item->tong,
            'orders' => (int) $item->so_don
        ];
    })) !!};

    if (chartData.length > 0 && document.getElementById('revenueOrdersChart')) {
        const ctx = document.getElementById('revenueOrdersChart').getContext('2d');
        
        const revenues = chartData.map(d => d.revenue);
        const maxRev = Math.max(...revenues);
        const minRev = Math.min(...revenues);
        const avgRev = revenues.reduce((a, b) => a + b, 0) / revenues.length;
        
        const backgroundColors = chartData.map(d => {
            if (chartData.length > 1 && d.revenue === maxRev) return '#2ECC71'; // Xanh lục cho ngày tốt nhất
            if (chartData.length > 1 && d.revenue === minRev) return '#E74C3C'; // Đỏ cho ngày tệ nhất
            return '#F1C40F'; // Vàng cho các ngày còn lại
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.date),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Doanh thu (VNĐ)',
                        data: chartData.map(d => d.revenue),
                        backgroundColor: backgroundColors,
                        borderWidth: 0,
                        borderRadius: 4,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Trung bình/ngày',
                        data: chartData.map(() => avgRev),
                        borderColor: '#95A5A6',
                        backgroundColor: '#95A5A6',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        yAxisID: 'y',
                        order: 0
                    },
                    {
                        type: 'line',
                        label: 'Số lượng đơn',
                        data: chartData.map(d => d.orders),
                        borderColor: '#2C3E50',
                        backgroundColor: '#2C3E50',
                        borderWidth: 2,
                        tension: 0.3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#2C3E50',
                        pointRadius: 4,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Doanh thu (VNĐ)' },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN');
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Số lượng đơn' }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.yAxisID === 'y') {
                                    label += context.parsed.y.toLocaleString('vi-VN') + 'đ';
                                } else {
                                    label += context.parsed.y + ' đơn';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
