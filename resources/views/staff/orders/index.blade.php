@extends('staff.layout.app')
@section('title', 'Lịch sử đơn hàng')
@section('breadcrumb', 'Quản lý / <strong>Lịch sử đơn hàng</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Lịch sử đơn hàng</h1>
        <p class="page-subtitle">Tra cứu đơn hàng theo thời gian và khách hàng</p>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="/staff/orders" class="flex-gap-10">
        <input type="text" name="order_code" class="form-control filter-search"
               placeholder="Mã đơn" value="{{ request('order_code') }}">
        <input type="text" name="customer_name" class="form-control filter-search"
               placeholder="Tên khách hàng" value="{{ request('customer_name') }}">
        <input type="date" name="date_start" class="form-control" value="{{ request('date_start') }}">
        <input type="date" name="date_end" class="form-control" value="{{ request('date_end') }}">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/staff/orders" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Mã đơn</th>
                    <th>Bàn</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái đơn</th>
                    <th>Thanh toán</th>
                    <th>Thời gian</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $i => $order)
                @php
                    $stt = ($orders->firstItem() ?? 1) + $i;
                    $statusClass = match($order->trang_thai_don) {
                        'chờ xác nhận' => 'badge-pending',
                        'đã xác nhận' => 'badge-brew',
                        'đang pha chế' => 'badge-brew',
                        'hoàn thành' => 'badge-done',
                        'đã hủy' => 'badge-cancelled',
                        default => 'badge-default',
                    };
                    $payClass = match($order->trang_thai_thanh_toan) {
                        'đã thanh toán' => 'badge-done',
                        'chưa thanh toán' => 'badge-pending',
                        default => 'badge-default',
                    };
                @endphp
                <tr>
                    <td>{{ $stt }}</td>
                    <td class="font-600">{{ $order->ma_don_hang ?? '#'.$order->id }}</td>
                    <td>{{ $order->banAn?->so_ban ?? '—' }}</td>
                    <td>{{ $order->nguoiDung?->ho_ten ?? $order->ten_khach_hang ?? '—' }}</td>
                    <td class="price-text">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</td>
                    <td><span class="badge {{ $statusClass }}">{{ ucfirst($order->trang_thai_don) }}</span></td>
                    <td><span class="badge {{ $payClass }}">{{ ucfirst($order->trang_thai_thanh_toan) }}</span></td>
                    <td class="text-muted text-12">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="/staff/orders/{{ $order->id }}" class="btn btn-primary btn-sm">Chi tiết</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="empty-state">Chưa có đơn hàng nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">Hiển thị {{ $orders->firstItem() }}-{{ $orders->lastItem() }} / {{ $orders->total() }}</span>
            {{ $orders->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
