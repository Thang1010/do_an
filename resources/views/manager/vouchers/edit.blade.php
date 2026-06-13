@extends('manager.layout.app')

@section('title', 'Sửa mã giảm giá')
@section('breadcrumb', 'Kinh doanh / Mã giảm giá & Khuyến mãi / <strong>Sửa</strong>')

@section('content')

<div class="page-header">
	<div>
		<h1 class="page-title">Sửa mã giảm giá {{ $voucher->ma_voucher }}</h1>
		<p class="page-subtitle">Cập nhật thông tin mã giảm giá</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.vouchers.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
	</div>
</div>

<div class="card" style="max-width: 980px;">
	<div class="card-header">
		<span class="card-title">Thông tin mã giảm giá</span>
	</div>
	<div class="card-body">
		<form method="POST" action="{{ route('manager.vouchers.update', $voucher->id) }}">
			@csrf
			@method('PUT')

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Mã giảm giá <span>*</span></label>
					<input type="text" name="ma_voucher" class="form-control text-uppercase" value="{{ old('ma_voucher', $voucher->ma_voucher) }}" required>
					@error('ma_voucher')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Tên chương trình</label>
					<input type="text" name="ten_voucher" class="form-control" value="{{ old('ten_voucher', $voucher->ten_voucher) }}">
					@error('ten_voucher')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Loại giảm giá <span>*</span></label>
					<select id="edit-loai-giam" name="loai_giam" class="form-control" onchange="toggleDiscountType(this, 'edit-discount-unit')" required>
						<option value="phần trăm" {{ old('loai_giam', $voucher->loai_giam) === 'phần trăm' ? 'selected' : '' }}>Phần trăm (%)</option>
						<option value="tiền mặt" {{ old('loai_giam', $voucher->loai_giam) === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt (đ)</option>
					</select>
					@error('loai_giam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái</label>
					<select name="trang_thai" class="form-control" required>
						<option value="đang hoạt động" {{ old('trang_thai', $voucher->trang_thai) === 'đang hoạt động' ? 'selected' : '' }}>Đang hoạt động</option>
						<option value="ngưng hoạt động" {{ old('trang_thai', $voucher->trang_thai) === 'ngưng hoạt động' ? 'selected' : '' }}>Ngưng hoạt động</option>
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
						<input type="text" name="gia_tri_giam" class="form-control format-money" value="{{ old('gia_tri_giam', $voucher->gia_tri_giam) }}" required>
						<span id="edit-discount-unit" class="discount-unit">{{ old('loai_giam', $voucher->loai_giam) === 'phần trăm' ? '%' : 'đ' }}</span>
					</div>
					@error('gia_tri_giam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Giá trị đơn tối thiểu (đ) <span>*</span></label>
					<input type="text" name="don_toi_thieu" class="form-control format-money" value="{{ old('don_toi_thieu', $voucher->don_toi_thieu) }}" required>
					@error('don_toi_thieu')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Ngày bắt đầu <span>*</span></label>
					<input type="datetime-local" name="ngay_bat_dau" class="form-control" value="{{ old('ngay_bat_dau', optional($voucher->ngay_bat_dau)->format('Y-m-d\\TH:i')) }}" required>
					@error('ngay_bat_dau')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Ngày kết thúc <span>*</span></label>
					<input type="datetime-local" name="ngay_ket_thuc" class="form-control" value="{{ old('ngay_ket_thuc', optional($voucher->ngay_ket_thuc)->format('Y-m-d\\TH:i')) }}" required>
					@error('ngay_ket_thuc')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Giới hạn sử dụng <span>*</span></label>
					<input type="number" name="so_luong" class="form-control" placeholder="Nhập 0 = không giới hạn" value="{{ old('so_luong', $voucher->so_luong) }}" min="0" required>
					@error('so_luong')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Giảm tối đa (đ) <span>*</span></label>
					<input type="text" name="giam_toi_da" class="form-control format-money" value="{{ old('giam_toi_da', $voucher->giam_toi_da) }}" required>
					@error('giam_toi_da')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
				<a href="{{ route('manager.vouchers.index') }}" class="btn btn-secondary">Hủy</a>
				<button type="submit" class="btn btn-primary">Lưu thay đổi</button>
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
	const select = document.getElementById('edit-loai-giam');
	if (select) {
		toggleDiscountType(select, 'edit-discount-unit');
	}
});
</script>
@endpush
