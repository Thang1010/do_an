@extends('manager.layout.app')

@section('title', 'Xuất kho')
@section('breadcrumb', 'Kho & Tài chính / <strong>Xuất kho</strong>')

@section('content')
<div class="page-header">
	<div>
		<h1 class="page-title">Xuất kho nguyên liệu</h1>
		<p class="page-subtitle">Ghi nhận xuất kho (hủy/hao hụt) và điều chỉnh kiểm kê tồn kho • {{ $purposeLabel ?? 'Tất cả' }}</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.inventory.index', array_filter(['muc_dich_su_dung' => $currentPurpose])) }}" class="btn btn-secondary">Về quản lý kho</a>
		<a href="{{ route('manager.inventory.import', array_filter(['muc_dich_su_dung' => $currentPurpose])) }}" class="btn btn-primary">Sang trang nhập kho</a>
	</div>
</div>

<div class="card">
	<div class="card-header">
		<span class="card-title">Phiếu xuất kho</span>
	</div>
	<div class="card-body">
		<form method="POST" action="{{ route('manager.inventory.export.store') }}">
			@csrf
			<input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurpose ?? '' }}">

			<div class="form-group">
				<label class="form-label">Nguyên liệu <span>*</span></label>
				<select name="nguyen_lieu_id" class="form-control" id="inventory-export-ingredient" required>
					<option value="">-- Chọn nguyên liệu --</option>
					@foreach($nguyenLieus as $nguyenLieu)
						<option
							value="{{ $nguyenLieu->id }}"
							data-unit="{{ $nguyenLieu->don_vi_tinh }}"
							{{ (string) old('nguyen_lieu_id', $selectedNguyenLieuId) === (string) $nguyenLieu->id ? 'selected' : '' }}
						>
							{{ $nguyenLieu->ten_nguyen_lieu }} (Tồn: {{ number_format((float) $nguyenLieu->so_luong, 2, ',', '.') }} {{ $nguyenLieu->don_vi_tinh }}) 
						</option>
					@endforeach
				</select>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Đơn vị tính</label>
					<input type="text" class="form-control" id="inventory-export-unit" value="" readonly>
				</div>
			</div>

			<div class="form-group">
				<label class="form-label">Số lượng xuất <span>*</span></label>
				<input
					type="number"
					step="0.01"
					min="0.01"
					name="so_luong"
					class="form-control"
					value="{{ old('so_luong') }}"
					placeholder="Ví dụ: 2.5"
					required
				>
			</div>

			<div class="form-group">
				<label class="form-label">Lý do xuất kho</label>
				<textarea
					name="ly_do"
					class="form-control"
					maxlength="500"
					placeholder="Xuất pha chế, hao hụt, điều chuyển..."
				>{{ old('ly_do') }}</textarea>
			</div>

			<button type="submit" class="btn btn-danger">Xác nhận xuất kho</button>
		</form>
	</div>
</div>

<div class="card mt-20">
	<div class="card-header">
		<span class="card-title">Phiếu kiểm kê / điều chỉnh</span>
	</div>
	<div class="card-body">
		<form method="POST" action="{{ route('manager.inventory.adjustment.store') }}">
			@csrf
			<input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurpose ?? '' }}">

			<div class="form-group">
				<label class="form-label">Nguyên liệu <span>*</span></label>
				<select name="nguyen_lieu_id" class="form-control" required>
					<option value="">-- Chọn nguyên liệu --</option>
					@foreach($nguyenLieus as $nguyenLieu)
						<option value="{{ $nguyenLieu->id }}" {{ (string) old('nguyen_lieu_id') === (string) $nguyenLieu->id ? 'selected' : '' }}>
							{{ $nguyenLieu->ten_nguyen_lieu }} (Tồn: {{ number_format((float) $nguyenLieu->so_luong, 2, ',', '.') }} {{ $nguyenLieu->don_vi_tinh }}) 
						</option>
					@endforeach
				</select>
			</div>

			<div class="form-group">
				<label class="form-label">Chênh lệch kiểm kê <span>*</span></label>
				<input
					type="number"
					step="0.01"
					name="chenh_lech"
					class="form-control"
					value="{{ old('chenh_lech') }}"
					placeholder="Nhập số dương để tăng, số âm để giảm"
					required
				>
				<p class="form-hint">Ví dụ: <strong>5</strong> để cộng thêm 5 đơn vị, hoặc <strong>-3</strong> để trừ 3 đơn vị.</p>
			</div>

			<div class="form-group">
				<label class="form-label">Lý do kiểm kê</label>
				<textarea
					name="ly_do_kiem_ke"
					class="form-control"
					maxlength="500"
					placeholder="Kiểm kê định kỳ, chênh lệch thực tế, điều chỉnh sai số..."
				>{{ old('ly_do_kiem_ke') }}</textarea>
			</div>

			<button type="submit" class="btn btn-warning">Lưu điều chỉnh kiểm kê</button>
		</form>
	</div>
</div>
@endsection

@push('scripts')
<script>
	function syncExportIngredientMeta() {
		const select = document.getElementById('inventory-export-ingredient');
		const selected = select ? select.options[select.selectedIndex] : null;
		const unitInput = document.getElementById('inventory-export-unit');

		if (!selected || !selected.value) {
			if (unitInput) unitInput.value = '';
			return;
		}

		if (unitInput) {
			unitInput.value = selected.getAttribute('data-unit') || '';
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		const select = document.getElementById('inventory-export-ingredient');
		if (select) {
			select.addEventListener('change', syncExportIngredientMeta);
		}
		syncExportIngredientMeta();
	});
</script>
@endpush
