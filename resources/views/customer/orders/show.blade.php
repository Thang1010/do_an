@extends('customer.layout.app')

@section('title', 'Chi tiết đơn hàng - XM Coffee')
@section('meta_description', 'Xem chi tiết đơn hàng tại XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
<style>
	.order-detail-card {
		background: rgba(30, 17, 6, 0.65);
		border: 1px solid rgba(240, 221, 184, 0.15);
		backdrop-filter: blur(20px);
		-webkit-backdrop-filter: blur(20px);
		border-radius: 20px;
		padding: 32px;
		color: #F5EFE4;
		box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
		position: relative;
		font-family: 'Be Vietnam Pro', 'Outfit', sans-serif;
	}

	.order-header-row {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		margin-bottom: 12px;
	}

	.badge-payment {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 6px 14px;
		border-radius: 50px;
		font-size: 13px;
		font-weight: 500;
		line-height: 1;
	}
	.badge-payment--paid {
		background: rgba(34, 197, 94, 0.15);
		border: 1px solid rgba(34, 197, 94, 0.3);
		color: #4ade80;
	}
	.badge-payment--unpaid {
		background: rgba(245, 158, 11, 0.15);
		border: 1px solid rgba(245, 158, 11, 0.3);
		color: #fbbf24;
	}

	.badge-payment-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background-color: currentColor;
	}

	.btn-back-custom {
		background: rgba(240, 221, 184, 0.1);
		border: 1px solid rgba(240, 221, 184, 0.35);
		color: #F0DDB8;
		padding: 6px 18px;
		border-radius: 999px;
		font-size: 13px;
		font-weight: 600;
		text-decoration: none;
		transition: all 0.2s;
		cursor: pointer;
	}
	.btn-back-custom:hover {
		background: rgba(240, 221, 184, 0.22);
		border-color: rgba(240, 221, 184, 0.6);
		color: #fff;
	}

	.order-info-section {
		text-align: center;
		margin-top: -15px;
		margin-bottom: 25px;
	}
	.order-info-subtitle {
		font-size: 11px;
		color: rgba(240, 221, 184, 0.5);
		font-weight: 600;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		margin-bottom: 4px;
	}
	.order-info-title {
		font-size: 24px;
		color: #F0DDB8;
		font-weight: 700;
		letter-spacing: 0.02em;
	}

	.meta-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-bottom: 1px solid rgba(240, 221, 184, 0.15);
		padding-bottom: 12px;
		margin-bottom: 16px;
		font-size: 14px;
	}
	.meta-left {
		color: #F5EFE4;
		font-weight: 500;
	}
	.meta-right {
		color: rgba(240, 221, 184, 0.6);
	}

	.table-title {
		font-size: 11px;
		color: rgba(240, 221, 184, 0.5);
		font-weight: 600;
		letter-spacing: 0.1em;
		text-transform: uppercase;
		margin-bottom: 10px;
		text-align: left;
	}

	.details-table {
		width: 100%;
		border-collapse: collapse;
		margin-bottom: 20px;
	}
	.details-table th {
		font-size: 11px;
		color: #fff;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		text-align: left;
		padding-bottom: 8px;
		border-bottom: 1px solid rgba(240, 221, 184, 0.15);
	}
	.details-table td {
		padding: 12px 0;
		font-size: 14px;
		border-bottom: 1px solid rgba(240, 221, 184, 0.1);
		vertical-align: middle;
	}
	.details-table tr:last-child td {
		border-bottom: none;
	}

	.item-name {
		color: #fff;
		font-weight: 600;
	}
	.item-size {
		color: #e2d9c8;
	}
	.item-note-badge {
		background: rgba(249, 115, 22, 0.15);
		border: 1px solid rgba(249, 115, 22, 0.3);
		color: #fb923c;
		font-size: 11px;
		padding: 2px 10px;
		border-radius: 999px;
		display: inline-block;
	}
	.item-qty {
		color: #fff;
		font-weight: 600;
		text-align: right;
	}

	.voucher-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 8px;
		font-size: 14px;
	}
	.voucher-label {
		color: rgba(240, 221, 184, 0.6);
	}
	.voucher-value {
		color: #f87171;
		font-weight: 600;
	}

	.total-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 12px;
		border-top: 1px solid rgba(240, 221, 184, 0.15);
		padding-top: 16px;
	}
	.total-label {
		font-size: 14px;
		font-weight: 700;
		color: rgba(240, 221, 184, 0.6);
		text-transform: uppercase;
		letter-spacing: 0.08em;
	}
	.total-value-container {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.total-value {
		font-size: 22px;
		font-weight: 700;
		color: #fff;
	}
</style>
@endpush

@section('content')
	@php
		$voucherCode = null;
		if ($order->voucherNguoiDung && $order->voucherNguoiDung->voucher) {
			$voucherCode = $order->voucherNguoiDung->voucher->ma_voucher;
		}
		$discountAmount = $order->so_tien_giam;
	@endphp

	<main class="cart-main">
		<div class="cart-container" style="max-width: 800px;">
			<div class="order-detail-card">
				
				<!-- Top row for status and back button -->
				<div class="order-header-row">
					<div>
						@if($order->trang_thai_thanh_toan === 'đã thanh toán')
							<span class="badge-payment badge-payment--paid">
								<span class="badge-payment-dot"></span>
								Đã thanh toán
							</span>
						@else
							<span class="badge-payment badge-payment--unpaid">
								<span class="badge-payment-dot"></span>
								Chưa thanh toán
							</span>
						@endif
					</div>
					<div>
						<a href="{{ route('customer.orders') }}" class="btn-back-custom">Quay lại</a>
					</div>
				</div>

				<!-- Center title and order ID -->
				<div class="order-info-section">
					<div class="order-info-subtitle">Thông tin chung</div>
					<h2 class="order-info-title">Đơn hàng #{{ $order->ma_don_hang ?? $order->id }}</h2>
				</div>

				<!-- Metadata: Table & Creation Date -->
				<div class="meta-row">
					<div class="meta-left">
						Bàn: {{ $order->banAn ? ($order->banAn->ten_ban ?? ('Bàn ' . $order->banAn->so_ban)) : 'Mang về' }}
					</div>
					<div class="meta-right">
						Ngày tạo: {{ optional($order->created_at)->format('d/m/Y H:i') }}
					</div>
				</div>

				<!-- Item Details Section -->
				<div class="table-title">Chi tiết món ăn</div>
				<table class="details-table">
					<thead>
						<tr>
							<th style="width: 35%;">Món</th>
							<th style="width: 15%;">Kích thước</th>
							<th style="width: 15%;">Ghi chú</th>
							<th style="width: 15%;">Số lượng</th>
							<th style="width: 20%; text-align: right;">Giá</th>
						</tr>
					</thead>
					<tbody>
						@foreach($order->chiTietDonHang as $item)
							<tr>
								<td class="item-name">{{ $item->ten_san_pham }}</td>
								<td class="item-size">
									@if($item->kichCo && $item->kichCo->ma_kich_co)
										{{ $item->kichCo->ma_kich_co }} ({{ $item->ten_kich_co }})
									@else
										{{ $item->ten_kich_co ?? '—' }}
									@endif
								</td>
								<td>
									@if($item->ghi_chu_mon)
										<span class="item-note-badge">{{ trim($item->ghi_chu_mon, '()') }}</span>
									@else
										—
									@endif
								</td>
								<td class="item-qty" style="text-align: left; padding-left: 8px;">{{ $item->so_luong }}</td>
								<td style="color: #F0DDB8; font-weight: 600; text-align: right;">{{ number_format($item->don_gia, 0, ',', '.') }}đ</td>
							</tr>
						@endforeach
					</tbody>
				</table>

				<!-- Voucher Section -->
				@if($discountAmount > 0)
					<div class="voucher-row">
						<span class="voucher-label">Mã giảm giá ({{ $voucherCode ?? 'VOUCHER' }}):</span>
						<span class="voucher-value">-{{ number_format($discountAmount, 0, ',', '.') }}đ</span>
					</div>
				@endif

				<!-- Total Section -->
				<div class="total-row">
					<span class="total-label">Thu nhập</span>
					<div class="total-value-container">
						<span class="total-value">{{ number_format($order->tong_tien, 0, ',', '.') }}đ</span>
					</div>
				</div>

			</div>
		</div>
	</main>
@endsection
