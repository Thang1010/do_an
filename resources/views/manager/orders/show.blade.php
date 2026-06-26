@extends('manager.layout.app')

@section('title', 'Chi tiết đơn hàng')
@section('breadcrumb')
	Kinh doanh / <a href="{{ route('manager.orders.index') }}">Quản lý đơn hàng</a> / <strong>Chi tiết đơn</strong>
@endsection

@push('styles')
<style>
	.order-type-pill {
		display: inline-block;
		color: #fff;
		font-size: 13px;
		font-weight: 700;
		padding: 4px 14px;
		border-radius: 999px;
		letter-spacing: .2px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, .12);
	}
	.order-type-takeaway { background: #ea580c; }
	.order-type-preorder { background: #7c3aed; }
	.order-type-qr       { background: #0891b2; }
	.order-type-instant  { background: #16a34a; }
	.order-type-default  { background: #6b7280; }
</style>
@endpush

@section('content')

	@php
		$voucher = $order->voucherNguoiDung?->voucher;
		$customerName = $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? 'Khách vãng lai';
		$customerPhone = $order->nguoiDung?->hoSoKhachHang?->so_dien_thoai ?? $order->nguoiDung?->hoSoNhanVien?->so_dien_thoai ?? '—';
		$loaiDon = \Illuminate\Support\Str::ucfirst($order->loai_don ?? '—');
		$loaiDonClass = match ($order->loai_don) {
			'mang về' => 'order-type-takeaway',          // cam — đơn mang về
			'đặt hàng trước' => 'order-type-preorder',   // tím — đặt trước
			'gọi tại bàn bằng qr' => 'order-type-qr',    // xanh ngọc — gọi tại bàn QR
			'sử dụng ngay' => 'order-type-instant',      // xanh lá — dùng ngay
			default => 'order-type-default',
		};
		$isTakeaway = ($order->loai_don === 'mang về');
		$daGiao = $order->da_giao_luc;
		$paymentStatus = $order->trang_thai_thanh_toan ?? '—';
		$paymentStatusClass = match ($paymentStatus) {
			'đã thanh toán' => 'badge-success',
			'chưa thanh toán' => 'badge-warning',
			'thất bại' => 'badge-danger',
			default => 'badge-default',
		};
	@endphp

	<div class="page-header">
		<div>
			<h1 class="page-title">Chi tiết đơn hàng #{{ $order->id }}</h1>
			<p class="page-subtitle">Mã đơn: {{ $order->ma_don_hang }} •
				{{ optional($order->created_at)->format('d/m/Y H:i') ?? '—' }}</p>
		</div>
		<div class="page-actions">
			<a href="{{ route('manager.orders.index') }}" class="btn btn-secondary">Quay lại</a>
		</div>
	</div>

	<div class="card mb-20">
		<div class="card-header" style="display: flex; align-items: center; gap: 12px;">
			<span class="card-title">Thông tin đơn hàng</span>
			<span style="background-color: #28a745; color: #ffffff; font-size: 14px; padding: 4px 12px; border-radius: 6px; font-weight: 600; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $paymentStatus }}</span>
		</div>
		<div class="card-body">
			<div class="form-grid-2">
				<div>
					<div class="text-12 text-muted">Tên người dùng</div>
					<div class="font-600">{{ $customerName }}</div>
				</div>
				<div>
					<div class="text-12 text-muted">Tên nhân viên nhận đơn</div>
					<div class="font-600">
						{{ $order->nhanVien?->hoSoNhanVien?->ho_ten ?? $order->nhanVien?->email ?? 'Chưa có' }}</div>
				</div>
				<div>
					<div class="text-12 text-muted">Số điện thoại</div>
					<div class="font-600">{{ $customerPhone }}</div>
				</div>
				<div>
					<div class="text-12 text-muted">Loại đơn</div>
					<div class="font-600"><span class="order-type-pill {{ $loaiDonClass }}">{{ $loaiDon }}</span></div>
				</div>
				<div>
					<div class="text-12 text-muted">Bàn</div>
					<div class="font-600">{{ $order->banAn->so_ban ?? ($order->loai_don === 'mang về' ? 'Mang về (không có bàn)' : 'Không có') }}</div>
				</div>
				<div>
					<div class="text-12 text-muted">{{ $order->loai_don === 'đặt hàng trước' ? 'Thời gian hẹn đến' : 'Dự kiến hoàn thành' }}</div>
					<div class="font-600">{{ $order->thoi_gian_den ? $order->thoi_gian_den->format('H:i d/m/Y') : '—' }}
					</div>
				</div>
				<div>
					<div class="text-12 text-muted">Phương thức thanh toán</div>
					<div class="font-600">{{ $order->phuong_thuc_thanh_toan ?? '—' }}</div>
				</div>
				@if($isTakeaway)
				<div>
					<div class="text-12 text-muted">Trạng thái giao</div>
					<div class="font-600">
						@if($daGiao)
							<span class="badge badge-done">Đã giao • {{ $daGiao->format('H:i d/m/Y') }}</span>
						@else
							<span class="badge badge-pending">Chưa giao</span>
						@endif
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<span class="card-title">Thông tin sản phẩm</span>
		</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr>
						<th>Tên sản phẩm</th>
						<th>Size</th>
						<th>Số lượng</th>
						<th>Giá gốc</th>
						<th>Giá khuyến mãi</th>
						<th>Giá theo kích cỡ</th>
						<th>Ghi chú món</th>
					</tr>
				</thead>
				<tbody>
					@forelse($order->chiTietDonHang ?? [] as $item)
						@php
							$sizeSymbol = $item->kichCo?->ma_kich_co
								?? (!empty($item->ten_kich_co) ? mb_strtoupper(mb_substr(trim($item->ten_kich_co), 0, 1)) : 'M');
							$giaGoc = $item->sanPham?->gia_goc ?? $item->don_gia ?? null;
							$giaKhuyenMai = $item->sanPham?->gia_khuyen_mai;
							if ($giaKhuyenMai === null && $item->don_gia !== null && $giaGoc !== null && (float) $item->don_gia < (float) $giaGoc) {
								$giaKhuyenMai = $item->don_gia;
							}
							// Giá đơn giá đã bao gồm hệ số kích cỡ (don_gia lưu trong chi_tiet_don_hang)
							$giaTheoKichCo = $item->don_gia;
						@endphp
						<tr>
							<td>
								<div class="font-600">{{ $item->ten_san_pham ?? $item->sanPham->ten_san_pham ?? '—' }}</div>
							</td>
							<td><span class="badge badge-default">{{ $sizeSymbol }}</span></td>
							<td>{{ number_format($item->so_luong ?? 0, 0, ',', '.') }}</td>
							<td>{{ $giaGoc !== null ? number_format($giaGoc, 0, ',', '.') . 'đ' : '—' }}</td>
							<td>{{ $giaKhuyenMai !== null ? number_format($giaKhuyenMai, 0, ',', '.') . 'đ' : '—' }}</td>
							<td class="price-text">
								{{ $giaTheoKichCo !== null ? number_format($giaTheoKichCo, 0, ',', '.') . 'đ' : '—' }}</td>
							<td class="text-muted">{{ $item->ghi_chu_mon ?: '—' }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="7" class="empty-state">Đơn hàng chưa có chi tiết sản phẩm.</td>
						</tr>
					@endforelse
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3" class="text-right text-12 text-muted font-600">Mã giảm giá đã sử dụng</td>
						<td colspan="2" class="font-600">{{ $voucher?->ma_voucher ?? 'Không dùng mã giảm giá' }}</td>
						<td class="text-right text-12 text-muted font-600">Số tiền đã giảm</td>
						<td class="font-600">{{ number_format($order->so_tien_giam ?? 0, 0, ',', '.') }}đ</td>
					</tr>
					<tr>
						<td colspan="6" class="text-right text-12 text-muted font-600">Tổng tiền cần trả</td>
						<td class="price-text text-22 font-700">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>

@endsection
