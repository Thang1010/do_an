@extends('staff.layout.app')
@section('title', 'Chi tiết lịch sử đơn hàng')
@section('breadcrumb')
<a href="{{ route('staff.orders.index') }}">Lịch sử đơn hàng</a> / <strong>Chi tiết #{{ $order->ma_don_hang ?? $order->id }}</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Đơn hàng #{{ $order->ma_don_hang ?? $order->id }}</h1>
        <p class="page-subtitle">{{ optional($order->created_at)->format('d/m/Y H:i') }}</p>
    </div>
    <div class="page-actions">
        <a href="/staff/orders" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<div class="grid-2 mb-20">
    <div class="card">
        <div class="card-header"><span class="card-title">Thông tin đơn</span></div>
        <div class="card-body">
            <div class="flex-col-10">
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Bàn</span>
                    <span class="font-600">{{ $order->banAn?->so_ban ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Khách hàng</span>
                    <span class="font-600">{{ $order->nguoiDung?->ho_ten ?? $order->ten_khach_hang ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Nhân viên</span>
                    <span class="font-600">{{ $order->nhanVien?->ho_ten ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Loại đơn</span>
                    <span class="font-600">{{ ucfirst($order->loai_don ?? '—') }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Trạng thái đơn</span>
                    @php $sc = match($order->trang_thai_don) {
                        'chờ xác nhận'=>'badge-pending','đã xác nhận'=>'badge-brew',
                        'đang pha chế'=>'badge-brew','hoàn thành'=>'badge-done',
                        'đã hủy'=>'badge-cancelled', default=>'badge-default'
                    }; @endphp
                    <span class="badge {{ $sc }}">{{ ucfirst($order->trang_thai_don) }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Thanh toán</span>
                    @php $pc = $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'badge-done' : 'badge-pending'; @endphp
                    <span class="badge {{ $pc }}">{{ ucfirst($order->trang_thai_thanh_toan) }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Phương thức</span>
                    <span class="font-600">{{ ucfirst($order->phuong_thuc_thanh_toan ?? '—') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Tổng kết</span></div>
        <div class="card-body">
            <div class="flex-col-10">
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Tạm tính</span>
                    <span class="font-600">{{ number_format($order->tam_tinh ?? 0, 0, ',', '.') }}đ</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Giảm giá</span>
                    <span class="font-600 text-success">-{{ number_format($order->so_tien_giam ?? 0, 0, ',', '.') }}đ</span>
                </div>
                <div class="divider"></div>
                <div class="flex-center-between">
                    <span class="font-600">Tổng cộng</span>
                    <span class="price-text text-22">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Danh sách món</span>
        <span class="text-12 text-muted">{{ $order->chiTietDonHang->count() }} món</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Size</th>
                    <th>Đơn giá</th>
                    <th>SL</th>
                    <th>Thành tiền</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->chiTietDonHang as $item)
                <tr>
                    <td class="font-600">{{ $item->ten_san_pham }}</td>
                    @php
                        $sizeCode = $item->kichCo?->ma_kich_co;
                        $sizeName = $item->kichCo?->ten_kich_co ?? $item->ten_kich_co;
                        $sizeLabel = $sizeCode && $sizeName ? $sizeCode . ' - ' . $sizeName : ($sizeName ?? 'M');
                    @endphp
                    <td><span class="badge badge-default">{{ $sizeLabel }}</span></td>
                    <td>{{ number_format($item->don_gia ?? 0, 0, ',', '.') }}đ</td>
                    <td>{{ $item->so_luong }}</td>
                    <td class="font-600">{{ number_format(($item->don_gia ?? 0) * ($item->so_luong ?? 1), 0, ',', '.') }}đ</td>
                    <td class="text-muted">{{ $item->ghi_chu_mon ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-state">Không có món nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($order->trang_thai_thanh_toan !== 'đã thanh toán')
<div class="payment-section" style="margin-top: 20px;">
    <div class="payment-section__title">Thanh toán cho đơn #{{ $order->ma_don_hang ?? $order->id }}</div>

    @if($store)
    <div class="payment-bank-info">
        <div class="payment-bank-row">
            <span class="payment-bank-row__label">STK:</span>
            <span class="payment-bank-row__value">{{ $store->so_tai_khoan ?? '—' }}</span>
        </div>
        <div class="payment-bank-row">
            <span class="payment-bank-row__label">Tên TK:</span>
            <span class="payment-bank-row__value">{{ $store->chuCuaHang?->ho_ten ?? $store->ten_cua_hang ?? '—' }}</span>
        </div>
        <div class="payment-bank-row">
            <span class="payment-bank-row__label">Ngân hàng:</span>
            <span class="payment-bank-row__value">{{ $store->ngan_hang ?? '—' }}</span>
        </div>
    </div>

    <div class="payment-qr" id="qr-panel" style="display:none;">
        <img id="qr-image" src="" alt="QR Thanh toán">
        <div class="payment-qr__hint" id="qr-hint">
            Sử dụng QR để thanh toán vào STK cửa hàng cho đơn hàng #{{ $order->ma_don_hang ?? $order->id }}
        </div>
        <div class="countdown-badge" id="qr-countdown" style="margin-top:8px; display:none;">
            Hết hạn: <span id="qr-timer">60</span>s
        </div>
    </div>

    <div style="text-align:center; margin-bottom: 12px;">
        <button type="button" class="btn btn-secondary btn-sm" id="generate-qr-btn"
                data-url="{{ route('staff.orders.payment-qr', $order->id) }}">
            Tạo mã QR thanh toán
        </button>
        <div class="text-12 text-muted mt-4" id="qr-message"></div>
    </div>
    @endif

    <div class="payment-total-bar">
        <span class="payment-total-label">Tổng phải trả:</span>
        <span class="payment-total-value">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</span>
    </div>

    <div class="payment-actions">
        <form method="POST" action="{{ route('staff.orders.payment.update', $order->id) }}" style="flex:1;">
            @csrf @method('PATCH')
            <input type="hidden" name="phuong_thuc_thanh_toan" value="{{ $order->phuong_thuc_thanh_toan ?? 'tiền mặt' }}">
            <input type="hidden" name="trang_thai_thanh_toan" value="chưa thanh toán">
            <button type="submit" class="btn btn-secondary w-full" style="justify-content:center;">Lưu tạm tính</button>
        </form>
        <form method="POST" action="{{ route('staff.orders.payment.update', $order->id) }}" style="flex:1;"
              onsubmit="return confirm('Xác nhận thanh toán cho đơn {{ $order->ma_don_hang ?? $order->id }}?')">
            @csrf @method('PATCH')
            <input type="hidden" name="phuong_thuc_thanh_toan" value="tiền mặt">
            <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;">Thanh toán</button>
        </form>
    </div>
</div>
@else
<div class="card" style="margin-top: 20px;">
    <div class="card-body" style="text-align:center; padding: 30px;">
        <div class="font-600 text-success" style="font-size: 20px; margin-bottom: 8px;">Đã thanh toán</div>
        <div class="font-600">Đơn hàng đã thanh toán</div>
        <div class="text-12 text-muted mt-4">{{ $order->phuong_thuc_thanh_toan ?? '' }}</div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function() {
    var btn = document.getElementById('generate-qr-btn');
    if (!btn) return;
    var panel = document.getElementById('qr-panel');
    var image = document.getElementById('qr-image');
    var countdown = document.getElementById('qr-countdown');
    var timer = document.getElementById('qr-timer');
    var message = document.getElementById('qr-message');
    var interval = null;

    btn.addEventListener('click', async function() {
        btn.disabled = true;
        message.textContent = 'Đang tạo mã QR...';
        message.style.color = '';
        try {
            var res = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Lỗi tạo QR');
            image.src = data.qr_url;
            panel.style.display = '';
            countdown.style.display = '';
            message.textContent = data.message || '';
            var remain = data.expires_in || 60;
            timer.textContent = remain;
            if (interval) clearInterval(interval);
            interval = setInterval(function() {
                remain--;
                timer.textContent = Math.max(remain, 0);
                if (remain <= 0) {
                    clearInterval(interval);
                    panel.style.display = 'none';
                    message.textContent = 'QR đã hết hạn. Vui lòng tạo lại.';
                    message.style.color = '#b42318';
                }
            }, 1000);
        } catch(e) {
            panel.style.display = 'none';
            message.textContent = e.message;
            message.style.color = '#b42318';
        }
        btn.disabled = false;
    });
})();
</script>
@endpush
