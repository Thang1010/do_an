@extends('layouts.manager')

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
					<th class="col-action-xl">Tùy chọn</th>
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

	@if(isset($categories) && method_exists($categories, 'hasPages') && $categories->hasPages())
	<div class="card-footer">
		<div class="pagination-footer">
			<span class="pagination-info">
				Hiển thị {{ $categories->firstItem() }}-{{ $categories->lastItem() }} / {{ $categories->total() }} danh mục
			</span>
			{{ $categories->links() }}
		</div>
	</div>
	@endif
</div>

{{-- Modal thêm danh mục --}}
<div class="modal-backdrop" id="create-category-modal">
	<div class="modal-box modal-md">
		<div class="modal-header">
			<span class="modal-title">Thêm danh mục mới</span>
			<button class="modal-close" onclick="closeModal('create-category-modal')">&#x2715;</button>
		</div>
		<div class="modal-body">
			<form id="create-category-form" method="POST" action="{{ route('manager.categories.store') }}">
				@csrf
				<div class="form-group">
					<label class="form-label">Tên danh mục <span>*</span></label>
					<input type="text" name="ten_danh_muc" class="form-control" maxlength="150" required>
				</div>
				<div class="form-group">
					<label class="form-label">Mô tả</label>
					<textarea name="mo_ta" class="form-control" rows="3" placeholder="Mô tả ngắn về danh mục..."></textarea>
				</div>
				<div class="form-group">
					<label class="form-label">Trạng thái</label>
					<select name="trang_thai" class="form-control">
						<option value="dang_dung">Đang dùng</option>
						<option value="ngung_dung">Ngưng dùng</option>
					</select>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button class="btn btn-secondary" onclick="closeModal('create-category-modal')">Hủy</button>
			<button class="btn btn-primary" onclick="document.getElementById('create-category-form').submit()">Lưu danh mục</button>
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
			<form id="edit-category-form-{{ $category->id }}" method="POST" action="{{ route('manager.categories.update', $category->id) }}">
				@csrf
				@method('PUT')
				<div class="form-group">
					<label class="form-label">Tên danh mục <span>*</span></label>
					<input
						type="text"
						name="ten_danh_muc"
						class="form-control"
						maxlength="150"
						value="{{ $category->ten_danh_muc }}"
						required
					>
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
			<button class="btn btn-primary" onclick="document.getElementById('edit-category-form-{{ $category->id }}').submit()">Cập nhật</button>
		</div>
	</div>
</div>
@endforeach

@endsection
