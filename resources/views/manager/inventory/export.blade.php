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
	</div>
</div>

<div class="tab-card mb-20">
	<div class="tab-list tab-list-inner">
		<button class="tab-btn active" data-tab-key="manual" onclick="activateExportTab('manual', this)">Xuất kho</button>
		@if(empty($selectedNguyenLieuId))
			<button class="tab-btn" data-tab-key="adjust" onclick="activateExportTab('adjust', this)">Kiểm kê / Điều chỉnh</button>
		@endif
	</div>

	<div class="tab-panel active p-24" id="tab-manual">
		<form method="POST" action="{{ route('manager.inventory.export.store') }}">
			@csrf
			<input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurpose ?? '' }}">

			<div id="export-items-container">
				<div class="export-item-row" data-index="0" style="display: flex; gap: 15px; align-items: flex-start; margin-bottom: 15px;">
					<div class="form-group" style="flex: 2; margin-bottom: 0;">
						<label class="form-label">Nguyên liệu <span>*</span></label>
						<select name="items[0][nguyen_lieu_id]" class="form-control export-ingredient-select" required onchange="syncExportItemUnit(this)">
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
						<input type="text" class="form-control export-unit-input" value="" readonly tabindex="-1" style="background-color: #f3f4f6; cursor: not-allowed; color: #6b7280;">
					</div>

					<div class="form-group" style="flex: 1; margin-bottom: 0;">
						<label class="form-label">Số lượng <span>*</span></label>
						<input type="number" step="0.01" min="0.01" name="items[0][so_luong]" class="form-control" value="{{ old('items.0.so_luong') }}" placeholder="Ví dụ: 2.5" required>
					</div>

					<div class="form-group" style="flex: 2; margin-bottom: 0;">
						<label class="form-label">Lý do xuất kho</label>
						<input type="text" name="items[0][ly_do]" class="form-control" value="{{ old('items.0.ly_do') }}" placeholder="Xuất pha chế, hao hụt...">
					</div>

					<div style="padding-top: 28px;">
						<button type="button" class="btn btn-danger btn-sm" onclick="removeExportItem(this)" {{ !empty($selectedNguyenLieuId) ? 'disabled style="display:none;"' : '' }}>&times;</button>
					</div>
				</div>
			</div>

			@if(empty($selectedNguyenLieuId))
			<div class="mb-20">
				<button type="button" class="btn btn-secondary btn-sm" onclick="addExportItem()">+ Thêm nguyên liệu</button>
			</div>
			@endif

			<button type="submit" class="btn btn-danger">Xác nhận xuất kho</button>
		</form>
	</div>

	@if(empty($selectedNguyenLieuId))
	<div class="tab-panel p-24" id="tab-adjust">
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
	@endif
</div>
@endsection

@push('scripts')
<script>
	function activateExportTab(tabId, btnElement) {
		const container = btnElement.closest('.tab-card');
		container.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
		container.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
		btnElement.classList.add('active');
		container.querySelector('#tab-' + tabId).classList.add('active');
	}

	function syncExportItemUnit(selectElement) {
		const selected = selectElement.options[selectElement.selectedIndex];
		const row = selectElement.closest('.export-item-row');
		const unitInput = row.querySelector('.export-unit-input');
		if (unitInput && selected) {
			unitInput.value = selected.getAttribute('data-unit') || '';
		}
	}

	let itemIndex = 1;
	function addExportItem() {
		const container = document.getElementById('export-items-container');
		const firstRow = container.querySelector('.export-item-row');
		const newRow = firstRow.cloneNode(true);
		
		newRow.dataset.index = itemIndex;
		
		// Reset values and update names
		const select = newRow.querySelector('.export-ingredient-select');
		select.name = `items[${itemIndex}][nguyen_lieu_id]`;
		select.selectedIndex = 0;
		
		const soLuongInput = newRow.querySelector('input[type="number"]');
		soLuongInput.name = `items[${itemIndex}][so_luong]`;
		soLuongInput.value = '';
		
		const lyDoInput = newRow.querySelector('input[type="text"]:not(.export-unit-input)');
		lyDoInput.name = `items[${itemIndex}][ly_do]`;
		lyDoInput.value = '';
		
		const unitInput = newRow.querySelector('.export-unit-input');
		unitInput.value = '';
		
		const removeBtn = newRow.querySelector('button.btn-danger');
		removeBtn.disabled = false;
		removeBtn.style.display = 'inline-block';
		
		container.appendChild(newRow);
		itemIndex++;
	}

	function removeExportItem(btn) {
		const row = btn.closest('.export-item-row');
		const container = document.getElementById('export-items-container');
		if (container.querySelectorAll('.export-item-row').length > 1) {
			row.remove();
		} else {
			showNotice('Phải có ít nhất 1 nguyên liệu để xuất kho.');
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		const selects = document.querySelectorAll('.export-ingredient-select');
		selects.forEach(select => syncExportItemUnit(select));
	});
</script>
@endpush
