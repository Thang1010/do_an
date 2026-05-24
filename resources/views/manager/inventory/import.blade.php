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
			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Nguyên liệu <span>*</span></label>
					<select name="nguyen_lieu_id" class="form-control" id="inventory-import-ingredient" required>
						<option value="">-- Chọn nguyên liệu --</option>
						@foreach($nguyenLieus as $nguyenLieu)
							<option
								value="{{ $nguyenLieu->id }}"
								data-name="{{ $nguyenLieu->ten_nguyen_lieu }}"
								data-unit="{{ $nguyenLieu->don_vi_tinh }}"
								{{ (string) old('nguyen_lieu_id', $selectedNguyenLieuId) === (string) $nguyenLieu->id ? 'selected' : '' }}
							>
								{{ $nguyenLieu->ten_nguyen_lieu }} (Tồn: {{ number_format((float) $nguyenLieu->so_luong, 2, ',', '.') }} {{ $nguyenLieu->don_vi_tinh }}) 
							</option>
						@endforeach
					</select>
				</div>
				<div class="form-group">
					<label class="form-label">Số lượng nhập <span>*</span></label>
					<input
						type="number"
						step="0.01"
						min="0.01"
						name="so_luong"
						class="form-control"
						value="{{ old('so_luong') }}"
						placeholder="Ví dụ: 10"
						required
					>
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Tên nguyên liệu</label>
					<input type="text" class="form-control" id="inventory-import-name" value="" readonly>
				</div>
				<div class="form-group">
					<label class="form-label">Đơn vị tính</label>
					<input type="text" class="form-control" id="inventory-import-unit" value="" readonly>
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Giá nhập / Sản phẩm</label>
					<input
						type="number"
						step="0.01"
						min="0"
						name="don_gia"
						class="form-control"
						value="{{ old('don_gia') }}"
						placeholder="Ví dụ: 25000"
					>
				</div>
				<div class="form-group">
					<label class="form-label">Ghi chú</label>
					<input
						type="text"
						name="ghi_chu"
						class="form-control"
						maxlength="500"
						value="{{ old('ghi_chu') }}"
						placeholder="Nhà cung cấp, mã lô..."
					>
				</div>
			</div>

			<button type="submit" class="btn btn-primary">Xác nhận nhập kho</button>
		</form>
	</div>
</div>

<div class="card">
	<div class="card-header">
		<span class="card-title">Nhập kho bằng file Excel</span>
	</div>
	<div class="card-body">
		<form method="POST" action="{{ route('manager.inventory.import.excel') }}" enctype="multipart/form-data">
			@csrf
			<input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurpose ?? '' }}">
			<div class="form-group mb-12">
				<label class="form-label">Chọn file Excel/CSV <span>*</span></label>
				<input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
				<p class="form-hint">Cột A: Tên hoặc ID nguyên liệu, cột B: Số lượng, cột C: Giá nhập / Sản phẩm (tùy chọn), cột D: Ghi chú (tùy chọn).</p>
			</div>
			<button type="submit" class="btn btn-secondary">Tải file và nhập kho</button>
		</form>
	</div>
</div>
@endsection

@push('scripts')
<script>
	function syncImportIngredientMeta() {
		const select = document.getElementById('inventory-import-ingredient');
		const selected = select ? select.options[select.selectedIndex] : null;
		const nameInput = document.getElementById('inventory-import-name');
		const unitInput = document.getElementById('inventory-import-unit');

		if (!selected || !selected.value) {
			if (nameInput) nameInput.value = '';
			if (unitInput) unitInput.value = '';
			return;
		}

		if (nameInput) {
			nameInput.value = selected.getAttribute('data-name') || '';
		}

		if (unitInput) {
			unitInput.value = selected.getAttribute('data-unit') || '';
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		const select = document.getElementById('inventory-import-ingredient');
		if (select) {
			select.addEventListener('change', syncImportIngredientMeta);
		}
		syncImportIngredientMeta();
	});
</script>
@endpush
