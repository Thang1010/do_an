@extends('customer.layout.app')

@section('title', 'Chi tiết đơn hàng - XM Coffee')
@section('meta_description', 'Xem chi tiết đơn hàng tại XM Coffee.')

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
					<h2 class="cart-modal-title">Đơn hàng #{{ $order->ma_don_hang ?? $order->id }}</h2>
					<a href="{{ route('customer.orders') }}" class="cart-modal-action">Quay lại</a>
				</div>

				<div class="cart-payment-meta" style="margin-bottom: 16px;">
					<div><strong>Ngày tạo:</strong> {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
					@if($order->banAn)
						<div><strong>Bàn:</strong> {{ $order->banAn->ten_ban ?? ('Bàn ' . $order->banAn->so_ban) }}</div>
					@endif
					<div><strong>Thanh toán:</strong> {{ $order->trang_thai_thanh_toan }}</div>
				</div>

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
	</main>
@endsection
