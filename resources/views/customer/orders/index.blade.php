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

				{{-- Tiêu đề + nút mua sắm --}}
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
					<div style="flex: 1; min-width: 100px;"></div>
					<div style="text-align: center; flex: 2; min-width: 250px;">
						<h2 style="font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 4px;">Lịch sử đơn hàng</h2>
						@if($tuNgay || $denNgay || $trangThai)
						<p style="font-size: 0.8rem; color: rgba(255,255,255,0.55); margin: 0;">Đang lọc · {{ $orders->total() }} đơn</p>
						@else
							<p style="font-size: 0.8rem; color: rgba(255,255,255,0.55); margin: 0;">{{ $orders->total() }} đơn hàng</p>
						@endif
					</div>
					<div style="flex: 1; text-align: right; min-width: 100px;">
					    <a href="{{ route('menu.index') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: #059669; color: #fff; text-decoration: none; border: 1px solid rgba(5, 150, 105, 0.5); display: inline-block;">Tiếp tục mua sắm</a>
					</div>
				</div>

				{{-- Form lọc --}}
				<form action="{{ route('customer.orders') }}" method="GET"
					style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 24px; background: rgba(255,255,255,0.04); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
					<div style="display: flex; flex-direction: column; gap: 4px;">
						<label style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 600;">Từ ngày</label>
						<input type="date" name="tu_ngay" value="{{ $tuNgay }}"
							class="cart-input" style="padding: 8px 12px; margin: 0; width: 160px; font-size: 0.875rem; color-scheme: dark;">
					</div>
					<div style="display: flex; flex-direction: column; gap: 4px;">
						<label style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 600;">Đến ngày</label>
						<input type="date" name="den_ngay" value="{{ $denNgay }}"
							class="cart-input" style="padding: 8px 12px; margin: 0; width: 160px; font-size: 0.875rem; color-scheme: dark;">
					</div>
					<div style="display: flex; flex-direction: column; gap: 4px;">
						<label style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 600;">Trạng thái</label>
						<select name="trang_thai"
							style="padding: 8px 12px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; font-size: 0.875rem; height: 38px;">
							<option value="">Tất cả</option>
							<option value="đã thanh toán" {{ $trangThai === 'đã thanh toán' ? 'selected' : '' }}>Đã thanh toán</option>
							<option value="chưa thanh toán" {{ $trangThai === 'chưa thanh toán' ? 'selected' : '' }}>Chưa thanh toán</option>
						</select>
					</div>
					<div style="display: flex; gap: 8px; align-items: flex-end; padding-bottom: 1px;">
						<button type="submit" class="cart-submit-btn" style="padding: 8px 20px; width: auto; margin: 0; font-size: 0.875rem;">Lọc</button>
						@if($tuNgay || $denNgay || $trangThai)
							<a href="{{ route('customer.orders') }}" class="cart-submit-btn"
								style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: rgba(255,255,255,0.14); color: #fff; text-decoration: none;">Xóa lọc</a>
						@endif
					</div>
				</form>

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
												@else
													<span style="color: #fcd34d; background: rgba(217, 119, 6, 0.2); border: 1px solid rgba(217, 119, 6, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Chưa thanh toán</span>
												@endif
											</td>
											<td style="padding: 14px 16px; text-align: center;">
												<div style="display: flex; gap: 8px; justify-content: center; align-items: center; white-space: nowrap;">
													<a href="{{ route('customer.orders.show', $order->id) }}" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff; text-decoration: none;">Xem chi tiết</a>
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
						<h2>Không tìm thấy đơn hàng nào</h2>
						<p>Thử thay đổi bộ lọc hoặc hãy chọn một món đồ uống thật ngon nhé!</p>
						<a href="{{ route('menu.index') }}" class="cart-go-menu-btn" style="margin-top: 12px;">Xem thực đơn</a>
					</div>
				@endif
			</div>
		</div>
	</main>
@endsection
