@extends('customer.layout.app')

@section('title', 'Thanh toán đơn hàng - XM Coffee')
@section('meta_description', 'Thanh toán đơn hàng của bạn tại XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endpush

@section('content')
    <main class="cart-main">
        <div class="cart-container">
            <div class="cart-items-panel" style="padding: 24px;">
                <div class="cart-modal-header" style="margin-bottom: 12px;">
                    <h2 class="cart-modal-title">Thanh toán đơn hàng</h2>
                    <span class="cart-item-note-display" style="display: inline-flex;">{{ $order->ma_don_hang }}</span>
                </div>
                <p class="cart-info-note" style="margin-bottom: 20px;">
                    Vui lòng chuyển khoản theo QR bên dưới để đơn hàng được xác nhận.
                </p>

                <div class="cart-payment-grid">
                    <div class="cart-payment-card">
                        @if($qrData)
                            <img src="{{ $qrData['qr_url'] }}" alt="QR thanh toán" class="cart-payment-qr" id="qr-img">
                            <div style="text-align: center; margin-top: 12px; margin-bottom: 20px;">
                                <button type="button" onclick="downloadQR('{{ $qrData['qr_url'] }}')" class="cart-submit-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: auto; padding: 8px 16px; font-size: 14px; background: rgba(255, 255, 255, 0.1); border: none; cursor: pointer; color: white;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    Tải ảnh QR
                                </button>
                            </div>
                            <div class="cart-payment-meta">
                                <div><strong>Số tiền:</strong> {{ number_format($order->tong_tien, 0, ',', '.') }}đ</div>
                                <div><strong>Ngân hàng:</strong> {{ $qrData['bank_name'] }}</div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <strong>Số tài khoản:</strong> <span id="payment-account-no">{{ $qrData['account_no'] }}</span>
                                    <button type="button" onclick="copyText('{{ $qrData['account_no'] }}', this)" style="background: none; border: none; cursor: pointer; color: #34d399; padding: 2px; display: inline-flex; align-items: center;" title="Sao chép">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </div>

                                <div><strong>Nội dung:</strong> {{ $qrData['transfer_content'] }}</div>
                            </div>
                        @else
                            <div class="cart-empty" style="padding: 32px;">
                                <h2>Chưa có thông tin thanh toán</h2>
                                <p>Vui lòng liên hệ cửa hàng để được hỗ trợ thanh toán.</p>
                            </div>
                        @endif
                    </div>

                    <div class="cart-payment-card">
                        <h3 class="cart-items-title" style="text-align: left;">Thông tin đơn hàng</h3>
                        <div class="cart-payment-order">
                            @foreach($order->chiTietDonHang as $item)
                                <div class="cart-payment-item">
                                    <div>
                                        <div class="cart-item-name">{{ $item->ten_san_pham }}</div>
                                        @if($item->ten_kich_co)
                                            <div class="cart-item-size">Size: {{ $item->ten_kich_co }}</div>
                                        @endif
                                        @if($item->ghi_chu_mon)
                                            <div class="cart-item-note-display" style="margin-top: 4px;">{{ $item->ghi_chu_mon }}</div>
                                        @endif
                                    </div>
                                    <div class="cart-item-price">x{{ $item->so_luong }}</div>
                                </div>
                            @endforeach
                        </div>
                        @if($order->so_tien_giam > 0)
                            <div style="display: flex; justify-content: space-between; margin-top: 16px; font-size: 14px; color: rgba(255,255,255,0.7);">
                                <span>Tạm tính</span>
                                <span>{{ number_format($order->tong_tien + $order->so_tien_giam, 0, ',', '.') }}đ</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 14px; color: #10b981;">
                                <span>Giảm voucher</span>
                                <span>-{{ number_format($order->so_tien_giam, 0, ',', '.') }}đ</span>
                            </div>
                        @endif
                        <div class="cart-summary-total" style="margin-top: {{ $order->so_tien_giam > 0 ? '12px' : '16px' }};">
                            <span>Tổng cộng</span>
                            <span>{{ number_format($order->tong_tien, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>

                <div class="cart-payment-actions">
                    <form method="POST" action="{{ route('cart.payment.confirm', $order->ma_don_hang) }}">
                        @csrf
                        <input type="hidden" name="order_code" value="{{ $order->ma_don_hang }}">
                        <button type="submit" class="cart-submit-btn" style="margin: 0;">Tôi đã chuyển khoản</button>
                    </form>
                    <form method="POST" action="{{ route('cart.cancel_guest') }}" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?');">
                        @csrf
                        <input type="hidden" name="order_code" value="{{ $order->ma_don_hang }}">
                        <button type="submit" class="cart-submit-btn" style="margin: 0; background: rgba(201, 64, 64, 0.7);">Hủy đơn</button>
                    </form>
                    <a href="{{ route('menu.index') }}" class="cart-submit-btn" style="margin: 0; background: rgba(255, 255, 255, 0.14); color: #fff; text-decoration: none;">Về menu</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function copyText(text, btn) {
            navigator.clipboard.writeText(text).then(function() {
                var originalColor = btn.style.color;
                var originalHtml = btn.innerHTML;
                btn.style.color = '#10b981';
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                setTimeout(function() {
                    btn.style.color = originalColor;
                    btn.innerHTML = originalHtml;
                }, 2000);
            }).catch(function(err) {
                console.error('Không thể sao chép', err);
            });
        }
        function downloadQR(url) {
            fetch(url)
                .then(response => response.blob())
                .then(blob => {
                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = blobUrl;
                    a.download = 'qr_thanh_toan.png';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(blobUrl);
                    document.body.removeChild(a);
                })
                .catch(err => {
                    console.error('Lỗi khi tải ảnh:', err);
                    window.open(url, '_blank');
                });
        }
    </script>
@endsection
