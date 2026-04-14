@extends('layouts.manager')

@section('title', 'Chi tiết đơn hàng')
@section('breadcrumb')
Kinh doanh / <a href="{{ route('manager.orders.index') }}">Quản lý đơn hàng</a> / <strong>Chi tiết đơn</strong>
@endsection

@section('content')

@php
	$voucher = $order->voucherNguoiDung?->voucher;
	$customerName = $order->nguoiDung->ho_ten ?? $order->ten_khach_hang ?? 'Khách vãng lai';
	$customerPhone = $order->nguoiDung->so_dien_thoai ?? $order->so_dien_thoai_khach ?? '—';
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
		<p class="page-subtitle">Mã đơn: {{ $order->ma_don_hang }} • {{ optional($order->created_at)->format('d/m/Y H:i') ?? '—' }}</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.orders.index') }}" class="btn btn-secondary">Quay lại</a>
	</div>
</div>

<div class="card mb-20">
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
					<th>Ghi chú món</th>
				</tr>
			</thead>
			<tbody>
				@forelse($order->chiTietDonHang ?? [] as $item)
				@php
					$sizeSymbol = $item->kichCo->ma_kich_co
						?? (!empty($item->ten_kich_co) ? mb_strtoupper(mb_substr(trim($item->ten_kich_co), 0, 1)) : 'M');
				@endphp
				<tr>
					<td>
						<div class="font-600">{{ $item->ten_san_pham ?? $item->sanPham->ten_san_pham ?? '—' }}</div>
					</td>
					<td><span class="badge badge-default">{{ $sizeSymbol }}</span></td>
					<td>{{ number_format($item->so_luong ?? 0, 0, ',', '.') }}</td>
					<td class="text-muted">{{ $item->ghi_chu_mon ?: '—' }}</td>
				</tr>
				@empty
				<tr>
					<td colspan="4" class="empty-state">Đơn hàng chưa có chi tiết sản phẩm.</td>
				</tr>
				@endforelse
			</tbody>
			<tfoot>
				<tr>
					<td class="text-right text-12 text-muted font-600">Voucher đã sử dụng</td>
					<td class="font-600">{{ $voucher?->ma_voucher ?? 'Không dùng voucher' }}</td>
					<td class="text-right text-12 text-muted font-600">Số tiền đã giảm</td>
					<td class="font-600">{{ number_format($order->so_tien_giam ?? 0, 0, ',', '.') }}đ</td>
				</tr>
				<tr>
					<td colspan="3" class="text-right text-12 text-muted font-600">Tổng tiền cần trả</td>
					<td class="price-text text-22 font-700">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="card">
	<div class="card-header">
		<span class="card-title">Thông tin đơn hàng</span>
	</div>
	<div class="card-body">
		<div class="form-grid-2">
			<div>
				<div class="text-12 text-muted">Tên người dùng</div>
				<div class="font-600">{{ $customerName }}</div>
			</div>
			<div>
				<div class="text-12 text-muted">Tên nhân viên nhận đơn</div>
				<div class="font-600">{{ $order->nhanVien->ho_ten ?? 'Chưa có' }}</div>
			</div>
			<div>
				<div class="text-12 text-muted">Số điện thoại</div>
				<div class="font-600">{{ $customerPhone }}</div>
			</div>
			<div>
				<div class="text-12 text-muted">Bàn</div>
				<div class="font-600">{{ $order->banAn->so_ban ?? 'Không có' }}</div>
			</div>
			<div>
				<div class="text-12 text-muted">Phương thức thanh toán</div>
				<div class="font-600">{{ $order->phuong_thuc_thanh_toan ?? '—' }}</div>
			</div>
			<div>
				<div class="text-12 text-muted">Trạng thái thanh toán</div>
				<div class="font-600"><span class="badge {{ $paymentStatusClass }}">{{ $paymentStatus }}</span></div>
			</div>
		</div>

		@if(auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên'], true))
		<form method="POST" action="{{ route('manager.orders.payment', $order->id) }}" style="margin-top: 18px; border-top: 1px dashed #e6ded2; padding-top: 14px;">
			@csrf
			@method('PATCH')
			<div class="text-12 text-muted" style="margin-bottom: 10px;">Cập nhật thủ công thanh toán (quản lý/nhân viên)</div>
			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Phương thức thanh toán</label>
					<select name="phuong_thuc_thanh_toan" class="form-control">
						<option value="">Chưa chọn</option>
						<option value="tiền mặt" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
						<option value="chuyển khoản" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
					</select>
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái thanh toán</label>
					<select name="trang_thai_thanh_toan" class="form-control" required>
						<option value="chưa thanh toán" {{ ($order->trang_thai_thanh_toan ?? '') === 'chưa thanh toán' ? 'selected' : '' }}>Chưa thanh toán</option>
						<option value="đã thanh toán" {{ ($order->trang_thai_thanh_toan ?? '') === 'đã thanh toán' ? 'selected' : '' }}>Đã thanh toán</option>
						<option value="thất bại" {{ ($order->trang_thai_thanh_toan ?? '') === 'thất bại' ? 'selected' : '' }}>Thất bại</option>
					</select>
				</div>
			</div>
			<div style="display: flex; justify-content: flex-end; margin-top: 8px;">
				<button type="submit" class="btn btn-primary btn-sm">Lưu thanh toán</button>
			</div>
		</form>
		@endif
		</div>
	</div>
</div>

@endsection
