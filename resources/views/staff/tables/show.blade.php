@extends('staff.layout.app')
@section('title', 'Chi tiết bàn')
@section('breadcrumb')
<a href="{{ route('staff.tables.index') }}">Bàn</a> / <strong>Chi tiết Bàn {{ $table->so_ban }}</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        @php
            $latestPaymentStatus = $latestOrder?->trang_thai_thanh_toan ?? '—';
            $latestPaymentStatusClass = match ($latestPaymentStatus) {
                'đã thanh toán' => 'badge-done',
                'chưa thanh toán' => 'badge-pending',
                default => 'badge-default',
            };
        @endphp
        <h1 class="page-title" style="display: flex; align-items: center; gap: 10px;">
            Chi tiết Bàn {{ $table->so_ban }}
            @if($latestOrder)
                <span class="badge {{ $latestPaymentStatusClass }} text-12">{{ mb_convert_case($latestPaymentStatus, MB_CASE_TITLE, 'UTF-8') }}</span>
            @endif
        </h1>
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
        @if($table->trang_thai === 'đang phục vụ' && (!$latestOrder || $latestOrder->trang_thai_thanh_toan === 'chưa thanh toán'))
            <form id="clear-table-form-show" method="POST" action="{{ route('staff.tables.clear', $table->id) }}" style="display:none;">
                @csrf @method('DELETE')
            </form>
            <button type="button" class="btn btn-danger btn-sm"
                    onclick="openClearTableModal()"
                    style="background:#d92d20;color:#fff;border:none;">
                Xóa thông tin bàn
            </button>
        @endif
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
                <div class="font-600">{{ $latestOrder->nguoiDung?->hoSoKhachHang?->ho_ten ?? $latestOrder->nguoiDung?->email ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Nhân viên</div>
                <div class="font-600">{{ $latestOrder->nhanVien?->hoSoNhanVien?->ho_ten ?? $latestOrder->nhanVien?->email ?? '—' }}</div>
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

{{-- Clear table modal --}}
@if($table->trang_thai === 'đang phục vụ' && $latestOrder)
<div id="clear-table-show-modal" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;" role="dialog" aria-modal="true">
    <div data-backdrop-clear style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(217, 45, 32, 0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d92d20" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:#F0DDB8;">Xóa thông tin bàn {{ $table->so_ban }}</div>
            <button type="button" onclick="closeClearTableModal()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.5);font-size:20px;line-height:1;">&times;</button>
        </div>
        <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:8px;line-height:1.6;">
            Hành động này sẽ <strong>xóa toàn bộ đơn hàng chưa thanh toán</strong> và tất cả món ăn trong bàn hiện tại.
        </p>
        <p style="font-size:14px;color:#ff6b6b;font-weight:600;margin-bottom:22px;">
            Bàn sẽ trở về trạng thái <em>trống</em>. Dữ liệu đơn hàng sẽ bị xóa vĩnh viễn và không thể khôi phục.
        </p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="closeClearTableModal()" style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Hủy</button>
            <button type="button" onclick="document.getElementById('clear-table-form-show').submit()" style="padding:10px 22px;border-radius:8px;border:none;background:#d92d20;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Xác nhận xóa bàn</button>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function openClearTableModal() {
    var m = document.getElementById('clear-table-show-modal');
    if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeClearTableModal() {
    var m = document.getElementById('clear-table-show-modal');
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}
(function() {
    var bd = document.querySelector('#clear-table-show-modal [data-backdrop-clear]');
    if (bd) bd.addEventListener('click', closeClearTableModal);
})();
</script>
@endpush
