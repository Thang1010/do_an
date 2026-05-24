@extends('manager.layout.app')

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
					<th>Giá gốc</th>
					<th>Giá khuyến mãi</th>
					<th>Giá theo kích cỡ</th>
					<th>Ghi chú món</th>
				</tr>
			</thead>
			<tbody>
				@forelse($order->chiTietDonHang ?? [] as $item)
				@php
					$sizeSymbol = $item->kichCo->ma_kich_co
						?? (!empty($item->ten_kich_co) ? mb_strtoupper(mb_substr(trim($item->ten_kich_co), 0, 1)) : 'M');
					$giaGoc = $item->sanPham?->gia_goc ?? $item->don_gia ?? null;
					$giaKhuyenMai = $item->sanPham?->gia_khuyen_mai;
					if ($giaKhuyenMai === null && $item->don_gia !== null && $giaGoc !== null && (float) $item->don_gia < (float) $giaGoc) {
						$giaKhuyenMai = $item->don_gia;
					}

					// Giá theo kích cỡ từ bảng san_pham_kich_co
					$giaTheoKichCo = null;
					if ($item->san_pham_id && $item->kich_co_id) {
						$sizeLink = \App\Models\SanPhamKichCo::where('san_pham_id', $item->san_pham_id)
							->where('kich_co_id', $item->kich_co_id)
							->first();
						if ($sizeLink) {
							$giaTheoKichCo = $sizeLink->gia_khuyen_mai ?? $sizeLink->gia_ban;
						}
					}
				@endphp
				<tr>
					<td>
						<div class="font-600">{{ $item->ten_san_pham ?? $item->sanPham->ten_san_pham ?? '—' }}</div>
					</td>
					<td><span class="badge badge-default">{{ $sizeSymbol }}</span></td>
					<td>{{ number_format($item->so_luong ?? 0, 0, ',', '.') }}</td>
					<td>{{ $giaGoc !== null ? number_format($giaGoc, 0, ',', '.') . 'đ' : '—' }}</td>
					<td>{{ $giaKhuyenMai !== null ? number_format($giaKhuyenMai, 0, ',', '.') . 'đ' : '—' }}</td>
					<td class="price-text">{{ $giaTheoKichCo !== null ? number_format($giaTheoKichCo, 0, ',', '.') . 'đ' : '—' }}</td>
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
					<td colspan="3" class="text-right text-12 text-muted font-600">Voucher đã sử dụng</td>
					<td colspan="2" class="font-600">{{ $voucher?->ma_voucher ?? 'Không dùng voucher' }}</td>
					<td class="text-right text-12 text-muted font-600">Số tiền đã giảm</td>
					<td class="font-600">{{ number_format($order->so_tien_giam ?? 0, 0, ',', '.') }}đ</td>
				</tr>
				<tr>
					<td colspan="6" class="text-right text-12 text-muted font-600">Tổng tiền cần trả</td>
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

		@if(auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên', 'chủ cửa hàng'], true))
		<div style="margin-top: 18px; border-top: 1px dashed #e6ded2; padding-top: 14px;">
			<div class="text-12 text-muted" style="margin-bottom: 10px;">Cập nhật thanh toán (bắt buộc chọn phương thức)</div>
			<form method="POST" action="{{ route('manager.orders.payment', $order->id) }}" id="order-payment-method-form">
				@csrf
				@method('PATCH')
				<input type="hidden" name="trang_thai_thanh_toan" value="{{ $order->trang_thai_thanh_toan ?? 'chưa thanh toán' }}">
				<div class="form-grid-2">
					<div class="form-group">
						<label class="form-label">Phương thức thanh toán <span>*</span></label>
						<select name="phuong_thuc_thanh_toan" id="order-payment-method-select" class="form-control" required>
							<option value="" disabled {{ empty($order->phuong_thuc_thanh_toan) ? 'selected' : '' }}>Chọn phương thức</option>
							<option value="tiền mặt" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
							<option value="chuyển khoản" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
						</select>
					</div>
					<div class="form-group" style="display: flex; align-items: flex-end;">
						<button type="submit" class="btn btn-secondary btn-sm">Lưu phương thức</button>
					</div>
				</div>
			</form>

			<div id="order-payment-actions" data-paid="{{ ($order->trang_thai_thanh_toan ?? '') === 'đã thanh toán' ? '1' : '0' }}">
				<div id="order-payment-paid" class="text-12 text-muted" style="margin-top: 10px; display: none;">
					Đơn hàng đã thanh toán thành công.
				</div>
				<div id="order-payment-hint" class="text-12 text-muted" style="margin-top: 10px; display: none;">
					Vui lòng chọn phương thức thanh toán để tiếp tục.
				</div>
				<div id="order-cash-action" style="margin-top: 12px; display: none;">
					<form method="POST" action="{{ route('manager.orders.payment', $order->id) }}"
						onsubmit="return confirm('Xác nhận đã nhận tiền mặt cho đơn này?')">
						@csrf
						@method('PATCH')
						<input type="hidden" name="phuong_thuc_thanh_toan" value="tiền mặt">
						<input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
						<button type="submit" class="btn btn-primary btn-sm">Xác nhận đã thanh toán</button>
					</form>
				</div>
				<div id="order-transfer-action" style="margin-top: 12px; display: none;">
					<div class="text-12 text-muted" style="margin-bottom: 10px;">Tạo mã QR thanh toán nhanh (hiệu lực 60 giây)</div>
					<button type="button"
							class="btn btn-primary btn-sm"
							id="order-generate-payment-qr-btn"
							data-url="{{ route('manager.orders.payment-qr', $order->id) }}">
						Tạo mã QR thanh toán
					</button>

					<div id="order-payment-qr-message" class="text-12 text-muted" style="margin-top: 10px;">Nhấn nút để tạo mã QR thanh toán.</div>

					<div id="order-payment-qr-panel" style="display: none; margin-top: 12px;">
						<img id="order-payment-qr-image" src="" alt="QR thanh toán" style="width: 220px; height: 220px; border-radius: 10px; border: 1px solid #e6ded2; background: #fff;">
						<div class="text-12" style="margin-top: 10px;">
							<div><strong>Ngân hàng:</strong> <span id="order-payment-qr-bank">—</span></div>
							<div><strong>Số tài khoản:</strong> <span id="order-payment-qr-account">—</span></div>
							<div><strong>Số tiền:</strong> <span id="order-payment-qr-amount">0đ</span></div>
							<div><strong>Nội dung CK:</strong> <span id="order-payment-qr-content">—</span></div>
							<div style="margin-top: 6px;"><strong>Hết hiệu lực sau:</strong> <span id="order-payment-qr-countdown">60</span>s</div>
						</div>

						<form id="order-confirm-qr-paid-form"
								method="POST"
								action="{{ route('manager.orders.payment', $order->id) }}"
								style="margin-top: 10px;"
								onsubmit="return confirm('Xác nhận đã nhận tiền qua QR cho đơn này?')">
							@csrf
							@method('PATCH')
							<input type="hidden" name="phuong_thuc_thanh_toan" value="chuyển khoản">
							<input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
							<button type="submit" class="btn btn-secondary btn-sm">Xác nhận đã nhận tiền QR</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		@endif
	</div>
</div>

@endsection

@push('scripts')
<script>
(function () {
	const methodSelect = document.getElementById('order-payment-method-select');
	const actions = document.getElementById('order-payment-actions');
	if (!actions) {
		return;
	}

	const paid = actions.dataset.paid === '1';
	const cashAction = document.getElementById('order-cash-action');
	const transferAction = document.getElementById('order-transfer-action');
	const hint = document.getElementById('order-payment-hint');
	const paidNote = document.getElementById('order-payment-paid');

	function updateActions() {
		if (paid) {
			if (paidNote) paidNote.style.display = 'block';
			if (cashAction) cashAction.style.display = 'none';
			if (transferAction) transferAction.style.display = 'none';
			if (hint) hint.style.display = 'none';
			return;
		}

		const method = methodSelect ? methodSelect.value : '';
		if (cashAction) cashAction.style.display = method === 'tiền mặt' ? 'block' : 'none';
		if (transferAction) transferAction.style.display = method === 'chuyển khoản' ? 'block' : 'none';
		if (hint) hint.style.display = method ? 'none' : 'block';
		if (paidNote) paidNote.style.display = 'none';
	}

	updateActions();

	if (methodSelect) {
		methodSelect.addEventListener('change', updateActions);
	}
})();

(function () {
	const btn = document.getElementById('order-generate-payment-qr-btn');
	if (!btn) {
		return;
	}

	const panel = document.getElementById('order-payment-qr-panel');
	const message = document.getElementById('order-payment-qr-message');
	const qrImage = document.getElementById('order-payment-qr-image');
	const bankEl = document.getElementById('order-payment-qr-bank');
	const accountEl = document.getElementById('order-payment-qr-account');
	const amountEl = document.getElementById('order-payment-qr-amount');
	const contentEl = document.getElementById('order-payment-qr-content');
	const countdownEl = document.getElementById('order-payment-qr-countdown');
	let countdownTimer = null;

	function setMessage(text, isError) {
		message.textContent = text;
		message.style.color = isError ? '#b42318' : '#5f544a';
	}

	function stopCountdown() {
		if (countdownTimer) {
			clearInterval(countdownTimer);
			countdownTimer = null;
		}
	}

	function startCountdown(seconds) {
		stopCountdown();
		let remain = Number(seconds) || 60;
		countdownEl.textContent = remain;

		countdownTimer = setInterval(function () {
			remain -= 1;
			countdownEl.textContent = Math.max(remain, 0);
			if (remain <= 0) {
				stopCountdown();
				panel.style.display = 'none';
				qrImage.src = '';
				setMessage('Mã QR đã hết hiệu lực. Vui lòng tạo mã mới.', true);
			}
		}, 1000);
	}

	btn.addEventListener('click', async function () {
		btn.disabled = true;
		setMessage('Đang tạo mã QR thanh toán...', false);

		try {
			const response = await fetch(btn.dataset.url, {
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': '{{ csrf_token() }}',
					'Accept': 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				}
			});

			const payload = await response.json();

			if (!response.ok) {
				throw new Error(payload.message || 'Không thể tạo mã QR thanh toán.');
			}

			qrImage.src = payload.qr_url;
			bankEl.textContent = payload.bank_name || payload.bank_code || '—';
			accountEl.textContent = payload.account_no || '—';
			amountEl.textContent = new Intl.NumberFormat('vi-VN').format(payload.amount || 0) + 'đ';
			contentEl.textContent = payload.transfer_content || '—';
			panel.style.display = 'block';
			startCountdown(payload.expires_in || 60);
			setMessage(payload.message || 'Đã tạo mã QR thanh toán.', false);
		} catch (error) {
			panel.style.display = 'none';
			qrImage.src = '';
			stopCountdown();
			setMessage(error.message || 'Có lỗi khi tạo mã QR.', true);
		} finally {
			btn.disabled = false;
		}
	});
})();
</script>
@endpush
