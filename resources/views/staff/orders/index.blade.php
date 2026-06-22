@extends('staff.layout.app')
@section('title', 'Lịch sử đơn hàng')
@section('breadcrumb')
Nhân viên / <strong>Lịch sử đơn hàng</strong>
@endsection

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Lịch sử đơn hàng</h1>
            <p class="page-subtitle">Các đơn hàng do bạn tạo — tra cứu theo thời gian và khách hàng</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" action="/staff/orders" class="flex-gap-10">
            <input type="date" name="date_start" class="form-control" value="{{ $dateStart }}">
            <input type="date" name="date_end" class="form-control" value="{{ $dateEnd }}">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="/staff/orders" class="btn btn-secondary">Xóa lọc</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Bàn</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $i => $order)
                        @php
                            $payClass = match ($order->trang_thai_thanh_toan) {
                                'đã thanh toán' => 'badge-done',
                                'chưa thanh toán' => 'badge-pending',
                                default => 'badge-default',
                            };
                        @endphp
                        <tr>
                            <td class="font-600">{{ $order->ma_don_hang ?? '#' . $order->id }}</td>
                            <td>
                                @if($order->loai_don === 'mang về')
                                    <span class="badge" style="background:#ea580c;color:#fff;">Mang về</span>
                                @else
                                    {{ $order->banAn?->so_ban ?? '—' }}
                                @endif
                            </td>
                            <td>{{ $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? '—' }}</td>
                            <td class="price-text">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $payClass }}">{{ ucfirst($order->trang_thai_thanh_toan) }}</span></td>
                            <td class="text-muted text-12">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="/staff/orders/{{ $order->id }}" class="btn btn-primary btn-sm">Chi tiết</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">Bạn chưa tạo đơn hàng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">
                <div class="pagination-footer">
                    <span class="pagination-info">Hiển thị {{ $orders->firstItem() }}-{{ $orders->lastItem() }} /
                        {{ $orders->total() }}</span>
                    {{ $orders->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection