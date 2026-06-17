@extends('manager.layout.app')

@section('title', 'Sửa mã giảm giá')
@section('breadcrumb', 'Kinh doanh / Mã giảm giá & Khuyến mãi / <strong>Sửa</strong>')

@section('content')

@php
	$lockedAttr  = $isLocked ? 'disabled' : '';
	$lockedStyle = $isLocked ? 'background:#f3f4f6; color:#6b7280; cursor:not-allowed;' : '';
	$lockedHint  = '<small style="display:block; margin-top:4px; color:#9ca3af; font-size:12px;">Không thể sửa — voucher đã được cấp</small>';
@endphp

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
		@if($isLocked)
			<div style="background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-size:14px;">
				<strong>Voucher đã được cấp cho {{ $issuedCount }} người dùng.</strong>
				Các điều khoản giảm giá (mã, loại giảm, giá trị, đơn tối thiểu, giảm tối đa, ngày bắt đầu) đã bị khóa để không phá vỡ cam kết với khách.
				Bạn vẫn có thể sửa <em>tên chương trình, trạng thái, ngày kết thúc</em> và <em>tăng giới hạn sử dụng</em>.
			</div>
		@endif
		<form method="POST" action="{{ route('manager.vouchers.update', $voucher->id) }}">
			@csrf
			@method('PUT')

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Mã giảm giá <span>*</span></label>
					<input type="text" name="ma_voucher" class="form-control text-uppercase" value="{{ old('ma_voucher', $voucher->ma_voucher) }}" style="{{ $lockedStyle }}" {{ $lockedAttr }} required>
					@if($isLocked) {!! $lockedHint !!} @endif
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
					<select id="edit-loai-giam" name="loai_giam" class="form-control" onchange="toggleDiscountType(this, 'edit-discount-unit')" style="{{ $lockedStyle }}" {{ $lockedAttr }} required>
						<option value="phần trăm" {{ old('loai_giam', $voucher->loai_giam) === 'phần trăm' ? 'selected' : '' }}>Phần trăm (%)</option>
						<option value="tiền mặt" {{ old('loai_giam', $voucher->loai_giam) === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt (đ)</option>
					</select>
					@if($isLocked) {!! $lockedHint !!} @endif
					@error('loai_giam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái</label>
					<select name="trang_thai" class="form-control" required>
						<option value="đang hoạt động" {{ old('trang_thai', $voucher->trang_thai) === 'đang hoạt động' ? 'selected' : '' }}>Đang hoạt động</option>
						<option value="ngừng phát hành" {{ old('trang_thai', $voucher->trang_thai) === 'ngừng phát hành' ? 'selected' : '' }}>Ngừng phát hành (người đã nhận vẫn dùng được)</option>
						<option value="ngưng hoạt động" {{ old('trang_thai', $voucher->trang_thai) === 'ngưng hoạt động' ? 'selected' : '' }}>Ngưng hoạt động (chặn tất cả)</option>
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
						<input type="text" name="gia_tri_giam" class="form-control format-money" value="{{ old('gia_tri_giam', $voucher->gia_tri_giam) }}" style="{{ $lockedStyle }}" {{ $lockedAttr }} required>
						<span id="edit-discount-unit" class="discount-unit">{{ old('loai_giam', $voucher->loai_giam) === 'phần trăm' ? '%' : 'đ' }}</span>
					</div>
					@if($isLocked) {!! $lockedHint !!} @endif
					@error('gia_tri_giam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Giá trị đơn tối thiểu (đ) <span>*</span></label>
					<input type="text" name="don_toi_thieu" class="form-control format-money" value="{{ old('don_toi_thieu', $voucher->don_toi_thieu) }}" style="{{ $lockedStyle }}" {{ $lockedAttr }} required>
					@if($isLocked) {!! $lockedHint !!} @endif
					@error('don_toi_thieu')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Ngày bắt đầu <span>*</span></label>
					<input type="datetime-local" name="ngay_bat_dau" class="form-control" value="{{ old('ngay_bat_dau', optional($voucher->ngay_bat_dau)->format('Y-m-d\\TH:i')) }}" style="{{ $lockedStyle }}" {{ $lockedAttr }} required>
					@if($isLocked) {!! $lockedHint !!} @endif
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
					@if($isLocked)
						<small style="display:block; margin-top:4px; color:#9ca3af; font-size:12px;">Không nhỏ hơn {{ $issuedCount }} (số đã phát hành), hoặc nhập 0 = không giới hạn</small>
					@endif
					@error('so_luong')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
				<div class="form-group">
					<label class="form-label">Giảm tối đa (đ) <span>*</span></label>
					<input type="text" name="giam_toi_da" class="form-control format-money" value="{{ old('giam_toi_da', $voucher->giam_toi_da) }}" style="{{ $lockedStyle }}" {{ $lockedAttr }} required>
					@if($isLocked) {!! $lockedHint !!} @endif
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
