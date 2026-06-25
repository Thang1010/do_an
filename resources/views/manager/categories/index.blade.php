@extends('manager.layout.app')

@section('title', 'Quản lý danh mục')
@section('breadcrumb', 'Kinh doanh / <strong>Quản lý danh mục</strong>')

@section('content')

<div class="page-header">
	<div>
		<h1 class="page-title">Quản lý danh mục</h1>
		<p class="page-subtitle">Quản lý nhóm sản phẩm trong menu</p>
	</div>
	<div class="page-actions">
		<button class="btn btn-primary" onclick="openModal('create-category-modal')">Thêm danh mục</button>
	</div>
</div>

<div class="filter-bar">
	<form method="GET" action="{{ route('manager.categories.index') }}" class="flex-gap-10">
		<input
			type="text"
			name="search"
			class="form-control filter-search"
			placeholder="Tìm theo tên hoặc slug..."
			value="{{ request('search') }}"
		>
		<select name="trang_thai" class="form-control">
			<option value="">Tất cả trạng thái</option>
			<option value="dang_dung" {{ request('trang_thai') === 'dang_dung' ? 'selected' : '' }}>Đang dùng</option>
			<option value="ngung_dung" {{ request('trang_thai') === 'ngung_dung' ? 'selected' : '' }}>Ngưng dùng</option>
		</select>
		<button type="submit" class="btn btn-primary">Lọc</button>
		<a href="{{ route('manager.categories.index') }}" class="btn btn-secondary">Xóa lọc</a>
	</form>
</div>

<div class="card">
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th class="col-stt">STT</th>
					<th>Tên danh mục</th>
					<th>Mô tả</th>
					<th>Trạng thái</th>
					<th>Sản phẩm</th>
					<th class="col-action-xl">Thao tác</th>
				</tr>
			</thead>
			<tbody>
				@forelse($categories ?? [] as $i => $category)
				@php
					$isActive = ($category->trang_thai ?? '') === 'đang dùng';
					$stt = method_exists($categories, 'firstItem') && $categories->firstItem()
						? ($categories->firstItem() + $i)
						: ($i + 1);
				@endphp
				<tr>
					<td>{{ $stt }}</td>
					<td>
						<div class="font-600">{{ $category->ten_danh_muc }}</div>
						<div class="text-12 text-muted">/{{ $category->slug }}</div>
					</td>
					<td>
						<div class="text-13">{{ \Illuminate\Support\Str::limit($category->mo_ta ?? 'Chưa có mô tả', 70) }}</div>
					</td>
					<td>
						<span class="badge {{ $isActive ? 'badge-active' : 'badge-inactive' }}">
							{{ $isActive ? 'Đang dùng' : 'Ngưng dùng' }}
						</span>
					</td>
					<td>{{ number_format($category->san_pham_count ?? 0, 0, ',', '.') }}</td>
					<td>
						<div class="action-row">
							<a href="{{ route('manager.categories.show', $category->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
							<button
								type="button"
								class="btn btn-secondary btn-sm"
								onclick="openModal('edit-category-modal-{{ $category->id }}')"
							>
								Sửa
							</button>
							<form method="POST" action="{{ route('manager.categories.destroy', $category->id) }}"
								  onsubmit="return confirmDelete(this, 'Xóa danh mục {{ addslashes($category->ten_danh_muc) }}?')">
								@csrf
								@method('DELETE')
								<button type="submit" class="btn btn-danger btn-sm">Xóa</button>
							</form>
						</div>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="6" class="empty-state">
						Chưa có danh mục nào. <button class="btn btn-link link-primary" onclick="openModal('create-category-modal')">Thêm ngay</button>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@include('manager.partials.pager', ['paginator' => $categories, 'label' => 'danh mục'])
</div>

{{-- Modal thêm danh mục --}}
<div class="modal-backdrop" id="create-category-modal">
	<div class="modal-box modal-md">
		<div class="modal-header">
			<span class="modal-title">Thêm danh mục mới</span>
			<button class="modal-close" onclick="closeModal('create-category-modal')">&#x2715;</button>
		</div>
		<div class="modal-body">
			@php $createErrors = $errors->getBag('createCategory'); @endphp
			<form id="create-category-form" method="POST" action="{{ route('manager.categories.store') }}">
				@csrf
				<div class="form-group">
					<label class="form-label">Tên danh mục <span>*</span></label>
					<input
						type="text"
						name="ten_danh_muc"
						class="form-control {{ $createErrors->has('ten_danh_muc') ? 'is-invalid' : '' }}"
						maxlength="150"
						value="{{ $createErrors->isNotEmpty() ? old('ten_danh_muc') : '' }}"
						required
					>
					@if($createErrors->has('ten_danh_muc'))
						<div class="text-13 text-danger mt-1">{{ $createErrors->first('ten_danh_muc') }}</div>
					@endif
				</div>
				<div class="form-group">
					<label class="form-label">Mô tả</label>
					<textarea name="mo_ta" class="form-control" rows="3" placeholder="Mô tả ngắn về danh mục...">{{ $createErrors->isNotEmpty() ? old('mo_ta') : '' }}</textarea>
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái</label>
					@php $createTrangThai = $createErrors->isNotEmpty() ? old('trang_thai') : 'dang_dung'; @endphp
					<select name="trang_thai" class="form-control">
						<option value="dang_dung" {{ $createTrangThai === 'dang_dung' ? 'selected' : '' }}>Đang dùng</option>
						<option value="ngung_dung" {{ $createTrangThai === 'ngung_dung' ? 'selected' : '' }}>Ngưng dùng</option>
					</select>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button class="btn btn-secondary" onclick="closeModal('create-category-modal')">Hủy</button>
			<button type="submit" form="create-category-form" class="btn btn-primary">Lưu danh mục</button>
		</div>
	</div>
</div>

{{-- Modal sửa danh mục --}}
@foreach($categories ?? [] as $category)
<div class="modal-backdrop" id="edit-category-modal-{{ $category->id }}">
	<div class="modal-box modal-md">
		<div class="modal-header">
			<span class="modal-title">Sửa danh mục</span>
			<button class="modal-close" onclick="closeModal('edit-category-modal-{{ $category->id }}')">&#x2715;</button>
		</div>
		<div class="modal-body">
			@php $editErrors = $errors->getBag('editCategory_' . $category->id); @endphp
			<form id="edit-category-form-{{ $category->id }}" method="POST" action="{{ route('manager.categories.update', $category->id) }}">
				@csrf
				@method('PUT')
				<div class="form-group">
					<label class="form-label">Tên danh mục <span>*</span></label>
					<input
						type="text"
						name="ten_danh_muc"
						class="form-control {{ $editErrors->has('ten_danh_muc') ? 'is-invalid' : '' }}"
						maxlength="150"
						value="{{ $editErrors->isNotEmpty() ? old('ten_danh_muc', $category->ten_danh_muc) : $category->ten_danh_muc }}"
						required
					>
					@if($editErrors->has('ten_danh_muc'))
						<div class="text-13 text-danger mt-1">{{ $editErrors->first('ten_danh_muc') }}</div>
					@endif
				</div>
				<div class="form-group">
					<label class="form-label">Mô tả</label>
					<textarea name="mo_ta" class="form-control" rows="3">{{ $category->mo_ta }}</textarea>
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái</label>
					<select name="trang_thai" class="form-control">
						<option value="dang_dung" {{ $category->trang_thai === 'đang dùng' ? 'selected' : '' }}>Đang dùng</option>
						<option value="ngung_dung" {{ $category->trang_thai === 'ngưng dùng' ? 'selected' : '' }}>Ngưng dùng</option>
					</select>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button class="btn btn-secondary" onclick="closeModal('edit-category-modal-{{ $category->id }}')">Hủy</button>
			<button type="submit" form="edit-category-form-{{ $category->id }}" class="btn btn-primary">Cập nhật</button>
		</div>
	</div>
</div>
@endforeach

@php
	$openEditCategoryId = null;
	foreach ($categories ?? [] as $category) {
		if ($errors->getBag('editCategory_' . $category->id)->isNotEmpty()) {
			$openEditCategoryId = $category->id;
			break;
		}
	}
@endphp

@if($errors->getBag('createCategory')->isNotEmpty() || $openEditCategoryId)
<script>
	document.addEventListener('DOMContentLoaded', function () {
		@if($errors->getBag('createCategory')->isNotEmpty())
			openModal('create-category-modal');
		@elseif($openEditCategoryId)
			openModal('edit-category-modal-{{ $openEditCategoryId }}');
		@endif
	});
</script>
@endif

@endsection
