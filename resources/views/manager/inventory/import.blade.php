@extends('manager.layout.app')

@section('title', 'Nhập kho')
@section('breadcrumb', 'Kho & Tài chính / <strong>Nhập kho</strong>')

@section('content')
<div class="page-header">
	<div>
		<h1 class="page-title">Nhập kho nguyên liệu</h1>
		<p class="page-subtitle">Nhập thủ công hoặc nhập hàng loạt bằng file Excel • {{ $purposeLabel ?? 'Tất cả' }}</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.inventory.index', array_filter(['muc_dich_su_dung' => $currentPurpose])) }}" class="btn btn-secondary">Về quản lý kho</a>
		<a href="{{ route('manager.inventory.export', array_filter(['muc_dich_su_dung' => $currentPurpose])) }}" class="btn btn-danger">Sang trang xuất kho</a>
	</div>
</div>

<div class="card mb-20">
	<div class="card-header">
		<span class="card-title">Nhập kho thủ công</span>
	</div>
	<div class="card-body">
		<form method="POST" action="{{ route('manager.inventory.import.store') }}">
			@csrf
			<input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurpose ?? '' }}">

			<div id="import-items-container">
				<div class="import-item-row" data-index="0" style="display: flex; gap: 15px; align-items: flex-start; margin-bottom: 15px;">
					<div class="form-group" style="flex: 2; margin-bottom: 0;">
						<label class="form-label">Nguyên liệu <span>*</span></label>
						<select name="items[0][nguyen_lieu_id]" class="form-control import-ingredient-select" required onchange="syncImportItemUnit(this)">
							<option value="">-- Chọn nguyên liệu --</option>
							@foreach($nguyenLieus as $nguyenLieu)
								<option
									value="{{ $nguyenLieu->id }}"
									data-unit="{{ $nguyenLieu->don_vi_tinh }}"
									{{ (string) old('items.0.nguyen_lieu_id', $selectedNguyenLieuId) === (string) $nguyenLieu->id ? 'selected' : '' }}
								>
									{{ $nguyenLieu->ten_nguyen_lieu }} (Tồn: {{ number_format((float) $nguyenLieu->so_luong, 2, ',', '.') }} {{ $nguyenLieu->don_vi_tinh }}) 
								</option>
							@endforeach
						</select>
					</div>

					<div class="form-group" style="flex: 1; margin-bottom: 0;">
						<label class="form-label">Đơn vị</label>
						<input type="text" class="form-control import-unit-input" value="" readonly tabindex="-1" style="background-color: #f3f4f6; cursor: not-allowed; color: #6b7280;">
					</div>

					<div class="form-group" style="flex: 1; margin-bottom: 0;">
						<label class="form-label">Số lượng nhập <span>*</span></label>
						<input type="number" step="0.01" min="0.01" name="items[0][so_luong]" class="form-control" value="{{ old('items.0.so_luong') }}" placeholder="Ví dụ: 10" required>
					</div>

					<div class="form-group" style="flex: 1.5; margin-bottom: 0;">
						<label class="form-label">Giá nhập/SP <span>*</span></label>
						<input type="number" step="0.01" min="0" name="items[0][don_gia]" class="form-control" value="{{ old('items.0.don_gia') }}" placeholder="Ví dụ: 25000" required>
					</div>

					<div class="form-group" style="flex: 2; margin-bottom: 0;">
						<label class="form-label">Ghi chú</label>
						<input type="text" name="items[0][ghi_chu]" class="form-control" value="{{ old('items.0.ghi_chu') }}" placeholder="Nhà cung cấp, lô hàng...">
					</div>

					<div style="padding-top: 28px;">
						<button type="button" class="btn btn-danger btn-sm" onclick="removeImportItem(this)" {{ !empty($selectedNguyenLieuId) ? 'disabled style="display:none;"' : '' }}>&times;</button>
					</div>
				</div>
			</div>

			@if(empty($selectedNguyenLieuId))
			<div class="mb-20">
				<button type="button" class="btn btn-secondary btn-sm" onclick="addImportItem()">+ Thêm nguyên liệu</button>
			</div>
			@endif

			<button type="submit" class="btn btn-primary">Xác nhận nhập kho</button>
		</form>
	</div>
</div>
@endsection

@push('scripts')
<script>
	function syncImportItemUnit(selectElement) {
		const selected = selectElement.options[selectElement.selectedIndex];
		const row = selectElement.closest('.import-item-row');
		const unitInput = row.querySelector('.import-unit-input');
		if (unitInput && selected) {
			unitInput.value = selected.getAttribute('data-unit') || '';
		}
	}

	let itemIndex = 1;
	function addImportItem() {
		const container = document.getElementById('import-items-container');
		const firstRow = container.querySelector('.import-item-row');
		const newRow = firstRow.cloneNode(true);
		
		newRow.dataset.index = itemIndex;
		
		const select = newRow.querySelector('.import-ingredient-select');
		select.name = `items[${itemIndex}][nguyen_lieu_id]`;
		select.selectedIndex = 0;
		
		const soLuongInput = newRow.querySelector(`input[name*="[so_luong]"]`);
		soLuongInput.name = `items[${itemIndex}][so_luong]`;
		soLuongInput.value = '';

		const donGiaInput = newRow.querySelector(`input[name*="[don_gia]"]`);
		donGiaInput.name = `items[${itemIndex}][don_gia]`;
		donGiaInput.value = '';
		
		const ghiChuInput = newRow.querySelector(`input[name*="[ghi_chu]"]`);
		ghiChuInput.name = `items[${itemIndex}][ghi_chu]`;
		ghiChuInput.value = '';
		
		const unitInput = newRow.querySelector('.import-unit-input');
		unitInput.value = '';
		
		const removeBtn = newRow.querySelector('button.btn-danger');
		removeBtn.disabled = false;
		removeBtn.style.display = 'inline-block';
		
		container.appendChild(newRow);
		itemIndex++;
	}

	function removeImportItem(btn) {
		const row = btn.closest('.import-item-row');
		const container = document.getElementById('import-items-container');
		if (container.querySelectorAll('.import-item-row').length > 1) {
			row.remove();
		} else {
			showNotice('Phải có ít nhất 1 nguyên liệu để nhập kho.');
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		const selects = document.querySelectorAll('.import-ingredient-select');
		selects.forEach(select => syncImportItemUnit(select));
	});
</script>
@endpush
