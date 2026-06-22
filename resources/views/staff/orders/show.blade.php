@extends('staff.layout.app')
@section('title', 'Chi tiết lịch sử đơn hàng')
@section('breadcrumb')
    <a href="{{ route('staff.orders.index') }}">Lịch sử đơn hàng</a> / <strong>Chi tiết
        #{{ $order->ma_don_hang ?? $order->id }}</strong>
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

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <div class="card">
            <div class="card-header"><span class="card-title">Thông tin đơn</span></div>
            <div class="card-body">
                <div class="flex-col-10">
                    <div class="flex-center-between">
                        <span class="text-12 text-muted">Bàn</span>
                        @if($order->loai_don === 'mang về')
                            <span class="badge" style="background:#ea580c;color:#fff;">Mang về</span>
                        @else
                            <span class="font-600">{{ $order->banAn?->so_ban ?? '—' }}</span>
                        @endif
                    </div>
                    <div class="flex-center-between">
                        <span class="text-12 text-muted">Khách hàng</span>
                        <span
                            class="font-600">{{ $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? '—' }}</span>
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
                        <span class="font-600">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex-center-between">
                        <span class="text-12 text-muted">Giảm giá</span>
                        <span
                            class="font-600 text-success">-{{ number_format($order->so_tien_giam ?? 0, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="divider"></div>
                    <div class="flex-center-between">
                        <span class="font-600">Tổng cộng</span>
                        <span class="price-text text-22">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                    </div>
                    @if($order->trang_thai_thanh_toan !== 'đã thanh toán')
                        <div style="margin-top: 15px;">
                            <button type="button" class="btn btn-primary w-full detail-action__btn" style="justify-content:center; height: 44px; font-size: 15px;" onclick="openPaymentModal()">
                                Thanh toán
                            </button>
                        </div>
                    @endif
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
                            <td class="font-600">
                                {{ number_format(($item->don_gia ?? 0) * ($item->so_luong ?? 1), 0, ',', '.') }}đ</td>
                            <td class="text-muted">{{ $item->ghi_chu_mon ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Không có món nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($order->trang_thai_thanh_toan !== 'đã thanh toán')
        <div class="modal" id="payment-modal">
            <div class="modal__backdrop" data-modal-close></div>
            <div class="modal__content">
                <div class="modal__header">
                    <div class="modal__title">Thanh toán - Đơn #{{ $order->ma_don_hang ?? $order->id }}</div>
                    <button type="button" class="modal__close" data-modal-close>&times;</button>
                </div>
                <div class="modal__body payment-modal__body">
                    <div class="payment-modal__summary">
                        <div class="payment-modal__subtitle">Tóm tắt đơn hàng</div>
                        <div class="payment-modal__items">
                            @forelse($order->chiTietDonHang as $item)
                                <div class="payment-modal__item">
                                    <div class="payment-modal__item-name">{{ $item->ten_san_pham }}</div>
                                    <div class="payment-modal__item-qty">x{{ $item->so_luong }}</div>
                                    <div class="payment-modal__item-price">
                                        {{ number_format(($item->don_gia ?? 0) * ($item->so_luong ?? 1), 0, ',', '.') }}đ
                                    </div>
                                </div>
                            @empty
                                <div class="detail-empty__text">Chưa có món nào</div>
                            @endforelse
                        </div>
                        <div class="payment-modal__total">
                            <span>Tổng cộng:</span>
                            <span class="payment-modal__total-value">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                    <div class="payment-modal__method">
                        <div class="payment-modal__subtitle" style="margin-bottom:8px;">Email nhận hóa đơn (Tuỳ chọn)</div>
                        <input form="payment-modal-form" type="email" name="email_khach_hang" value="{{ $order->email_khach_hang ?? $order->nguoiDung?->email ?? '' }}" placeholder="email@example.com" class="form-control" style="margin-bottom:20px;">
                        
                        <div class="payment-modal__subtitle">Phương thức thanh toán</div>
                        <div class="payment-modal__methods">
                            <button type="button" class="payment-method" data-payment-method="tiền mặt">Tiền mặt</button>
                            <button type="button" class="payment-method is-active" data-payment-method="chuyển khoản">Chuyển khoản</button>
                        </div>
                        <div class="payment-modal__qr" id="payment-modal-qr" style="display:none; margin-top: 16px; text-align: center;">
                            <div id="payos-qr-container-staff" style="width:100%; height:490px; display:none; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: 0; left: 50%; margin-left: -200px; width: 400px; height: 650px; transform: scale(0.75); transform-origin: top center;">
                                    <iframe id="payos-qr-iframe-staff" src="" style="width:100%; height:100%; border:none; border-radius:12px; display:none;" allow="clipboard-write"></iframe>
                                </div>
                                <p style="font-size:15px;color:#16a34a;font-weight:700;display:none;position:absolute;bottom:0;width:100%;text-align:center;background:#fff;padding:8px 0;" id="payos-success-text-staff">Đã thanh toán thành công!</p>
                            </div>
                            <button type="button" class="btn btn-primary" id="btn-generate-payos-staff" style="display: inline-flex; justify-content: center; width: 100%;" onclick="generatePayOSQrStaff('{{ $order->ma_don_hang ?? '' }}')">
                                Tạo QR thanh toán PayOS
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal__footer payment-modal__footer" id="payment-modal-footer">
                    <form method="POST" action="{{ route('staff.orders.payment.update', $order->id) }}" class="payment-modal__actions" id="payment-modal-form">
                        @csrf @method('PATCH')
                        <input type="hidden" name="phuong_thuc_thanh_toan" id="payment-method-input" value="chuyển khoản">
                        <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                        <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                        <button type="submit" class="btn btn-primary" id="payment-modal-submit">Hoàn tất thanh toán</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
@include('partials.payos-payment')
<script>
function openPaymentModal() {
    var modal = document.getElementById('payment-modal');
    if (modal) {
        modal.classList.add('modal--open');
        initPaymentModal();
    }
}

function initPaymentModal() {
    var modal = document.getElementById('payment-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    var closes = modal.querySelectorAll('[data-modal-close]');
    closes.forEach(function(el) {
        el.addEventListener('click', function() {
            modal.classList.remove('modal--open');
        });
    });

    var methodButtons = modal.querySelectorAll('[data-payment-method]');
    var methodInput = document.getElementById('payment-method-input');
    var qrBox = document.getElementById('payment-modal-qr');
    
    function setMethod(method) {
        if (!method) return;
        if (methodInput) methodInput.value = method;
        methodButtons.forEach(function(btn) {
            btn.classList.toggle('is-active', btn.dataset.paymentMethod === method);
        });
        var submitBtn = document.getElementById('payment-modal-submit');
        if (method === 'chuyển khoản') {
            if (qrBox) qrBox.style.display = 'block';
            if (submitBtn) submitBtn.style.display = 'none';
        } else {
            if (qrBox) qrBox.style.display = 'none';
            if (submitBtn) submitBtn.style.display = 'inline-flex';
        }
    }

    methodButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            setMethod(btn.dataset.paymentMethod);
        });
    });

    setMethod('chuyển khoản');
}

function generatePayOSQrStaff(orderCode) {
    const emailInput = document.querySelector('input[name="email_khach_hang"]');
    PayOSPayment.start({
        orderCode: orderCode,
        source: 'staff',
        email: emailInput ? emailInput.value : '',
        button: document.getElementById('btn-generate-payos-staff'),
        iframe: document.getElementById('payos-qr-iframe-staff'),
        container: document.getElementById('payos-qr-container-staff'),
        successText: document.getElementById('payos-success-text-staff'),
        onPaid: function () { window.location.reload(); }
    });
}
</script>
@endpush