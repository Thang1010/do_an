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
                            <img src="{{ $qrData['qr_url'] }}" alt="QR thanh toán" class="cart-payment-qr">
                            <div class="cart-payment-meta">
                                <div><strong>Số tiền:</strong> {{ number_format($order->tong_tien, 0, ',', '.') }}đ</div>
                                <div><strong>Ngân hàng:</strong> {{ $qrData['bank_name'] }}</div>
                                <div><strong>Số tài khoản:</strong> {{ $qrData['account_no'] }}</div>
                                <div><strong>Chủ tài khoản:</strong> {{ $qrData['account_name'] }}</div>
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
                        <div class="cart-summary-total" style="margin-top: 16px;">
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
@endsection
