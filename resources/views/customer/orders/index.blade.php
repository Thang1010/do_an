@extends('customer.layout.app')

@section('title', 'Lịch sử đơn hàng - XM Coffee')
@section('meta_description', 'Theo dõi các đơn hàng của bạn tại XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endpush

@section('content')
	<main class="cart-main">
		<div class="cart-container">
			<div class="cart-orders-today" style="margin-top: 12px; margin-bottom: 40px; background: rgba(30, 17, 6, 0.5); border-radius: 24px; padding: 28px 24px; border: 1px solid rgba(255, 255, 255, 0.12); backdrop-filter: blur(14px);">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
					<h2 style="font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0;">Lịch sử đơn hàng</h2>
					<a href="{{ route('menu.index') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: #059669; color: #fff; text-decoration: none; border: 1px solid rgba(5, 150, 105, 0.5);">Tiếp tục mua sắm</a>
				</div>

				@if($orders->count() > 0)
					<div style="background: rgba(255, 255, 255, 0.03); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.08);">
						<div style="overflow-x: auto;">
							<table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 640px; white-space: nowrap;">
								<thead>
									<tr style="background: rgba(255, 255, 255, 0.06); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
										<th style="padding: 14px 16px; font-weight: 600; color: #fff;">Mã đơn</th>
										<th style="padding: 14px 16px; font-weight: 600; color: #fff;">Số sản phẩm</th>
										<th style="padding: 14px 16px; font-weight: 600; color: #fff;">Tổng tiền</th>
										<th style="padding: 14px 16px; font-weight: 600; color: #fff;">Trạng thái</th>
										<th style="padding: 14px 16px; font-weight: 600; text-align: center; color: #fff;">Thao tác</th>
									</tr>
								</thead>
								<tbody>
									@foreach($orders as $order)
										<tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
											<td style="padding: 14px 16px; font-weight: 500; color: #F5EFE4;">#{{ $order->ma_don_hang ?? $order->id }}</td>
											<td style="padding: 14px 16px; color: rgba(255, 255, 255, 0.78);">{{ $order->chi_tiet_don_hang_count ?? 0 }}</td>
											<td style="padding: 14px 16px; color: #F0DDB8; font-weight: 700;">{{ number_format($order->tong_tien, 0, ',', '.') }}đ</td>
											<td style="padding: 14px 16px;">
												@if($order->trang_thai_thanh_toan === 'đã thanh toán')
													<span style="color: #6ee7b7; background: rgba(5, 150, 105, 0.2); border: 1px solid rgba(5, 150, 105, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Đã thanh toán</span>
												@elseif(in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan']))
													<span style="color: #fcd34d; background: rgba(217, 119, 6, 0.2); border: 1px solid rgba(217, 119, 6, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Chưa xác nhận</span>
												@elseif(in_array($order->trang_thai_don, ['đã hủy', 'huy']))
													<span style="color: #fca5a5; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Đã hủy</span>
												@else
													<span style="color: #93c5fd; background: rgba(37, 99, 235, 0.2); border: 1px solid rgba(37, 99, 235, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Đã xác nhận</span>
												@endif
											</td>
											<td style="padding: 14px 16px; text-align: center;">
												<div style="display: flex; gap: 8px; justify-content: center; align-items: center; white-space: nowrap;">
													<a href="{{ route('customer.orders.show', $order->id) }}" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff; text-decoration: none;">Xem chi tiết</a>
													@if(in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan']))
														<form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?');">
															@csrf @method('PATCH')
															<button type="submit" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(201, 64, 64, 0.6); color: #fff; border: 1px solid rgba(201, 64, 64, 0.8);">Hủy đơn</button>
														</form>
													@endif
												</div>
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>

					<div style="margin-top: 16px;">
						{{ $orders->links() }}
					</div>
				@else
					<div class="cart-empty" style="border: none; background: transparent; box-shadow: none; padding: 40px 20px;">
						<svg class="cart-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
								d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
						</svg>
						<h2>Bạn chưa có đơn hàng nào</h2>
						<p>Hãy chọn một món đồ uống thật ngon nhé!</p>
						<a href="{{ route('menu.index') }}" class="cart-go-menu-btn" style="margin-top: 12px;">Xem thực đơn</a>
					</div>
				@endif
			</div>
		</div>
	</main>
@endsection
