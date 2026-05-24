@extends('staff.layout.app')
@section('title', 'Chi tiết bàn')
@section('breadcrumb')
<a href="{{ route('staff.tables.index') }}">Bàn</a> / <strong>Chi tiết Bàn {{ $table->so_ban }}</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Chi tiết Bàn {{ $table->so_ban }}</h1>
        <p class="page-subtitle">
            @if($latestOrder)
                Đơn gần nhất #{{ $latestOrder->ma_don_hang ?? $latestOrder->id }}
            @else
                Chưa có đơn hàng nào
            @endif
        </p>
    </div>
    <div class="page-actions">
        <a href="/staff/tables" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Danh sách món</span>
        <span class="text-12 text-muted">{{ $dishItems->total() ?? 0 }} món</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tên sản phẩm</th>
                    <th>Size</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dishItems as $item)
                <tr>
                    <td class="font-600">{{ $item->ten_san_pham ?? '—' }}</td>
                    @php
                        $sizeCode = $item->kichCo?->ma_kich_co;
                        $sizeName = $item->kichCo?->ten_kich_co ?? $item->ten_kich_co;
                        $sizeLabel = $sizeCode && $sizeName ? $sizeCode . ' - ' . $sizeName : ($sizeName ?? 'M');
                    @endphp
                    <td><span class="badge badge-default">{{ $sizeLabel }}</span></td>
                    <td>{{ number_format($item->don_gia ?? 0, 0, ',', '.') }}đ</td>
                    <td>{{ $item->so_luong ?? 0 }}</td>
                    <td class="font-600">{{ number_format(($item->don_gia ?? 0) * ($item->so_luong ?? 1), 0, ',', '.') }}đ</td>
                    <td class="text-muted">{{ $item->ghi_chu_mon ?: '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">Bàn này chưa có món nào.</td>
                </tr>
                @endforelse
            </tbody>
            @if($totalPayable > 0)
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right text-12 text-muted font-600">Tổng tiền cần trả</td>
                    <td colspan="2" class="price-text text-22 font-700">{{ number_format($totalPayable, 0, ',', '.') }}đ</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if(isset($dishItems) && method_exists($dishItems, 'hasPages') && $dishItems->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $dishItems->firstItem() }}-{{ $dishItems->lastItem() }} / {{ $dishItems->total() }} món
            </span>
            {{ $dishItems->links() }}
        </div>
    </div>
    @endif
</div>

@if($latestOrder)
<div class="card">
    <div class="card-header">
        <span class="card-title">Thông tin đơn hàng</span>
    </div>
    <div class="card-body">
        <div class="form-grid-2">
            <div>
                <div class="text-12 text-muted">Khách hàng</div>
                <div class="font-600">{{ $latestOrder->nguoiDung?->ho_ten ?? $latestOrder->ten_khach_hang ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Nhân viên</div>
                <div class="font-600">{{ $latestOrder->nhanVien?->ho_ten ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Phương thức TT</div>
                <div class="font-600">{{ $latestOrder->phuong_thuc_thanh_toan ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Trạng thái TT</div>
                <div class="font-600">
                    @php
                        $payClass = match($latestOrder->trang_thai_thanh_toan) {
                            'đã thanh toán' => 'badge-done',
                            'chưa thanh toán' => 'badge-pending',
                            default => 'badge-default',
                        };
                    @endphp
                    <span class="badge {{ $payClass }}">{{ $latestOrder->trang_thai_thanh_toan }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
