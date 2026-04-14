@extends('layouts.manager')

@section('title', 'Tạo voucher mới')
@section('breadcrumb', 'Kinh doanh / Voucher & Khuyến mãi / <strong>Tạo mới</strong>')

@section('content')

<div class="page-header">
	<div>
		<h1 class="page-title">Tạo voucher mới</h1>
		<p class="page-subtitle">Tạo mã giảm giá mới cho cửa hàng</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.vouchers.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
	</div>
</div>

<div class="card" style="max-width: 980px;">
	<div class="card-header">
		<span class="card-title">Thông tin voucher</span>
	</div>
	<div class="card-body">
		<form id="create-voucher-form" method="POST" action="{{ route('manager.vouchers.store') }}">
			@csrf
			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Mã voucher <span>*</span></label>
					<input type="text" name="ma_voucher" class="form-control text-uppercase"
						   placeholder="VD: SUMMER20" value="{{ old('ma_voucher') }}" required>
					<div class="form-hint">Chỉ dùng chữ hoa và số</div>
					@error('ma_voucher')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Tên voucher</label>
					<input type="text" name="ten_voucher" class="form-control" placeholder="Tên hiển thị voucher" value="{{ old('ten_voucher') }}">
					@error('ten_voucher')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Loại giảm giá <span>*</span></label>
					<select name="loai_giam" id="create-loai-giam" class="form-control" onchange="toggleDiscountType(this, 'create-discount-unit')" required>
						<option value="phần trăm" {{ old('loai_giam', 'phần trăm') === 'phần trăm' ? 'selected' : '' }}>Phần trăm (%)</option>
						<option value="tiền mặt" {{ old('loai_giam') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt (đ)</option>
					</select>
					@error('loai_giam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái</label>
					<select name="trang_thai" class="form-control">
						<option value="đang hoạt động" {{ old('trang_thai', 'đang hoạt động') === 'đang hoạt động' ? 'selected' : '' }}>Đang hoạt động</option>
						<option value="ngưng hoạt động" {{ old('trang_thai') === 'ngưng hoạt động' ? 'selected' : '' }}>Ngưng hoạt động</option>
					</select>
					@error('trang_thai')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Giá trị giảm <span>*</span></label>
					<div class="flex-center-gap-6">
						<input type="number" name="gia_tri_giam" class="form-control" placeholder="15" min="0" value="{{ old('gia_tri_giam') }}" required>
						<span id="create-discount-unit" class="discount-unit">%</span>
					</div>
					@error('gia_tri_giam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Giá trị đơn tối thiểu (đ)</label>
					<input type="number" name="don_toi_thieu" class="form-control" placeholder="100000" min="0" value="{{ old('don_toi_thieu') }}">
					@error('don_toi_thieu')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Ngày bắt đầu <span>*</span></label>
					<input type="datetime-local" name="ngay_bat_dau" class="form-control" value="{{ old('ngay_bat_dau') }}" required>
					@error('ngay_bat_dau')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Ngày kết thúc <span>*</span></label>
					<input type="datetime-local" name="ngay_ket_thuc" class="form-control" value="{{ old('ngay_ket_thuc') }}" required>
					@error('ngay_ket_thuc')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Giới hạn sử dụng</label>
					<input type="number" name="so_luong" class="form-control" placeholder="Để trống = không giới hạn" min="1" value="{{ old('so_luong') }}">
					@error('so_luong')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Giảm tối đa (đ)</label>
					<input type="number" name="giam_toi_da" class="form-control" placeholder="Ví dụ: 50000" min="0" value="{{ old('giam_toi_da') }}">
					@error('giam_toi_da')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
				<a href="{{ route('manager.vouchers.index') }}" class="btn btn-secondary">Hủy</a>
				<button type="submit" class="btn btn-primary">Tạo voucher</button>
			</div>
		</form>
	</div>
</div>

@endsection

@push('scripts')
<script>
function toggleDiscountType(sel, unitId) {
	const unit = document.getElementById(unitId);
	if (!unit) return;
	unit.textContent = sel.value === 'phần trăm' ? '%' : 'đ';
}

document.addEventListener('DOMContentLoaded', function () {
	const select = document.getElementById('create-loai-giam');
	if (select) {
		toggleDiscountType(select, 'create-discount-unit');
	}
});
</script>
@endpush
