@extends('manager.layout.app')

@section('title', 'Thêm ca làm việc')
@section('breadcrumb', 'Nhân sự / Quản lý ca làm việc / <strong>Thêm mới</strong>')

@section('content')

@php
	$oldSelected = collect((array) old('selected_user_ids', []))->map(fn($id) => (string) $id)->values()->all();
@endphp

<div class="page-header">
	<div>
		<h1 class="page-title">Thêm ca làm việc</h1>
		<p class="page-subtitle">Điền thông tin ca, sau đó lấy danh sách nhân sự rảnh theo chức vụ để phân ca</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.shifts.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
	</div>
</div>

<div class="card" style="max-width: 1080px;">
	<div class="card-header">
		<span class="card-title">Thông tin ca làm việc</span>
	</div>
	<div class="card-body">
		<form id="create-shift-form" method="POST" action="{{ route('manager.shifts.store') }}"
			  data-available-url="{{ route('manager.shifts.available-users') }}"
			  data-old-selected='@json($oldSelected)'>
			@csrf

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Tên ca <span>*</span></label>
					<input type="text" name="ten_ca" class="form-control" maxlength="100" value="{{ old('ten_ca') }}" required>
					@error('ten_ca')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>

				<div class="form-group">
					<label class="form-label">Ngày làm <span>*</span></label>
					<input type="date" name="ngay_lam" id="shift-date-input" class="form-control" value="{{ old('ngay_lam', \Carbon\Carbon::today()->toDateString()) }}" required>
					@error('ngay_lam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Giờ bắt đầu <span>*</span></label>
					<input type="time" name="gio_bat_dau" class="form-control" value="{{ old('gio_bat_dau') }}" required>
					@error('gio_bat_dau')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>

				<div class="form-group">
					<label class="form-label">Giờ kết thúc <span>*</span></label>
					<input type="time" name="gio_ket_thuc" class="form-control" value="{{ old('gio_ket_thuc') }}" required>
					@error('gio_ket_thuc')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-group">
				<div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
					<label class="form-label" style="margin:0;">Nhân sự rảnh <span>*</span></label>
					<button type="button" class="btn btn-secondary btn-sm" id="btn-load-staff">Lấy danh sách nhân sự</button>
				</div>
				<div id="available-users-box" style="margin-top:10px; border:1px solid #e5e7eb; border-radius:8px; padding:14px; max-height:420px; overflow:auto;">
					<div class="empty-state" id="available-users-placeholder" style="padding:8px 0;">
						Điền ngày + giờ bắt đầu/kết thúc rồi bấm <strong>“Lấy danh sách nhân sự”</strong>.
					</div>
					<div id="available-users-list" style="display:none;"></div>
				</div>
				@error('selected_user_ids')
					<div class="form-error">{{ $message }}</div>
				@enderror
				@error('selected_user_ids.*')
					<div class="form-error">{{ $message }}</div>
				@enderror
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
				<a href="{{ route('manager.shifts.index') }}" class="btn btn-secondary">Hủy</a>
				<button type="submit" class="btn btn-primary">Lưu ca làm việc</button>
			</div>
		</form>
	</div>
</div>

@endsection

@push('styles')
<style>

.role-group { border: 1px solid #edf2f7; border-radius: 8px; margin-bottom: 10px; }
.role-group > summary {
	cursor: pointer; user-select: none; list-style: none;
	display: flex; align-items: center; justify-content: space-between;
	gap: 10px; padding: 10px 12px; font-weight: 600;
}
.role-group > summary::-webkit-details-marker { display: none; }
.role-group > summary .role-meta { font-weight: 500; font-size: 12px; color: #8a6d3b; }
.role-group[data-loai="0"] > summary { background: #f4ecdd; }
.role-group[data-loai="1"] > summary { background: #eef5ff; }
.role-group[data-loai="2"] > summary { background: #f6f6f6; color: #6b7280; }
.role-group__body { padding: 6px 12px 12px; }
.available-user-item { display: flex; align-items: center; gap: 10px; padding: 7px 4px; }
.available-user-item + .available-user-item { border-top: 1px solid #f4f1ec; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
	const form = document.getElementById('create-shift-form');
	const dateInput = document.getElementById('shift-date-input');
	const listEl = document.getElementById('available-users-list');
	const placeholderEl = document.getElementById('available-users-placeholder');
	const loadBtn = document.getElementById('btn-load-staff');
	const availableUrl = form ? form.dataset.availableUrl : '';
	let oldSelected = [];
	try { oldSelected = JSON.parse(form?.dataset.oldSelected || '[]').map(String); } catch (e) { oldSelected = []; }

	// ── Lấy danh sách nhân sự ──
	function getValues() {
		const start = document.querySelector('input[name="gio_bat_dau"]')?.value || '';
		const end = document.querySelector('input[name="gio_ket_thuc"]')?.value || '';
		const date = dateInput?.value || '';
		return { date, start, end };
	}

	function checkedIds() {
		return Array.from(listEl.querySelectorAll('input[name="selected_user_ids[]"]:checked')).map((c) => c.value);
	}

	function showPlaceholder(html) {
		placeholderEl.innerHTML = html;
		placeholderEl.style.display = 'block';
		listEl.style.display = 'none';
		listEl.innerHTML = '';
	}

	function escapeHtml(str) {
		return String(str ?? '').replace(/[&<>"']/g, (s) => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
		}[s]));
	}

	async function loadStaff() {
		const { date, start, end } = getValues();
		if (!date || !start || !end) {
			showPlaceholder('Vui lòng điền đầy đủ <strong>ngày, giờ bắt đầu và giờ kết thúc</strong> trước.');
			return;
		}
		if (start === end) {
			showPlaceholder('Giờ bắt đầu và giờ kết thúc không được trùng nhau.');
			return;
		}

		const keep = new Set(checkedIds().concat(oldSelected));
		oldSelected = [];

		loadBtn.disabled = true;
		const originalText = loadBtn.textContent;
		loadBtn.textContent = 'Đang tải...';

		const params = new URLSearchParams({ ngay_lam: date, gio_bat_dau: start, gio_ket_thuc: end });
		try {
			const res = await fetch(`${availableUrl}?${params.toString()}`, {
				headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
			});
			if (!res.ok) { showPlaceholder('Không tải được danh sách nhân viên rảnh.'); return; }
			const data = await res.json();
			renderGroups(data.groups || [], keep);
		} catch (e) {
			showPlaceholder('Không tải được danh sách nhân viên rảnh.');
		} finally {
			loadBtn.disabled = false;
			loadBtn.textContent = originalText;
		}
	}

	function renderGroups(groups, keep) {
		const totalUsers = groups.reduce((sum, g) => sum + (g.users ? g.users.length : 0), 0);
		if (!totalUsers) {
			showPlaceholder('Không có nhân viên nào rảnh trong khung giờ này.');
			return;
		}

		placeholderEl.style.display = 'none';
		listEl.style.display = 'block';
		listEl.innerHTML = groups.map((g, idx) => {
			const items = (g.users || []).map((u) => {
				const checked = keep.has(String(u.id)) ? 'checked' : '';
				return `<label class="available-user-item">
					<input type="checkbox" name="selected_user_ids[]" value="${u.id}" ${checked}>
					<span>${escapeHtml(u.ho_ten)}</span>
				</label>`;
			}).join('');

			return `<details class="role-group" data-loai="${g.loai_order}">
				<summary>
					<span>${escapeHtml(g.ten_chuc_vu)}</span>
					<span class="role-meta">${(g.users || []).length} người</span>
				</summary>
				<div class="role-group__body">${items}</div>
			</details>`;
		}).join('');
	}

	if (loadBtn) loadBtn.addEventListener('click', loadStaff);

	// Sau khi submit lỗi validate mà đã có sẵn ngày/giờ → tự nạp lại để giữ lựa chọn cũ
	const initial = getValues();
	if (oldSelected.length && initial.date && initial.start && initial.end) {
		loadStaff();
	}
});
</script>
@endpush
