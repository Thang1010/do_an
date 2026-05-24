@extends('manager.layout.app')

@section('title', 'Thêm ca làm việc')
@section('breadcrumb', 'Nhân sự / Quản lý ca làm việc / <strong>Thêm mới</strong>')

@section('content')

@php
	$assignmentMode = old('assignment_mode', 'manual');
	$oldManagerIds = collect((array) old('selected_manager_ids', []))->map(fn($id) => (string) $id)->all();
	$oldStaffIds = collect((array) old('selected_staff_ids', []))->map(fn($id) => (string) $id)->all();
	$oldPositionCounts = collect((array) old('position_counts', []));
	$oldManagerCount = (int) old('manager_count', 0);
	$hasPositionCounts = $oldPositionCounts->filter(fn($count) => (int) $count > 0)->isNotEmpty();
	$useManagerCount = old('auto_use_manager_count', $oldManagerCount > 0 ? '1' : '0') === '1';
	$usePositionCounts = old('auto_use_position_counts', $hasPositionCounts ? '1' : '0') === '1';
	$staffPositionList = collect($staffPositions ?? [])->filter(fn($name) => trim((string) $name) !== '')->values();
@endphp

<div class="page-header">
	<div>
		<h1 class="page-title">Thêm ca làm việc</h1>
		<p class="page-subtitle">Tạo ca mới và phân công nhân sự</p>
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
		<form id="create-shift-form" method="POST" action="{{ route('manager.shifts.store') }}">
			@csrf
			<input type="hidden" name="add_mode" value="manual">

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
					<input type="date" name="ngay_lam" class="form-control" value="{{ old('ngay_lam') }}" required>
					@error('ngay_lam')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-grid-2">
				<div class="form-group">
					<label class="form-label">Giờ bắt đầu <span>*</span></label>
					<div class="time-spin-wrap" data-time-spin>
						<div class="time-spin-fields">
							<input type="number" min="0" max="23" step="1" class="form-control time-spin-input" data-time-hour placeholder="HH" autocomplete="off">
							<span class="time-spin-separator">:</span>
							<input type="number" min="0" max="59" step="1" class="form-control time-spin-input" data-time-minute placeholder="mm" autocomplete="off">
						</div>
						<input type="hidden" name="gio_bat_dau" value="{{ old('gio_bat_dau') }}" data-time-hidden>
					</div>
					@error('gio_bat_dau')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>

				<div class="form-group">
					<label class="form-label">Giờ kết thúc <span>*</span></label>
					<div class="time-spin-wrap" data-time-spin>
						<div class="time-spin-fields">
							<input type="number" min="0" max="23" step="1" class="form-control time-spin-input" data-time-hour placeholder="HH" autocomplete="off">
							<span class="time-spin-separator">:</span>
							<input type="number" min="0" max="59" step="1" class="form-control time-spin-input" data-time-minute placeholder="mm" autocomplete="off">
						</div>
						<input type="hidden" name="gio_ket_thuc" value="{{ old('gio_ket_thuc') }}" data-time-hidden>
					</div>
					@error('gio_ket_thuc')
						<div class="form-error">{{ $message }}</div>
					@enderror
				</div>
			</div>

			<div class="form-group">
				<label class="form-label">Cách phân công <span>*</span></label>
				<div style="display:flex; gap:18px; flex-wrap:wrap;">
					<label style="display:flex; align-items:center; gap:6px;">
						<input type="radio" name="assignment_mode" value="manual" {{ $assignmentMode !== 'auto' ? 'checked' : '' }}>
						<span>Phân công thủ công</span>
					</label>
					<label style="display:flex; align-items:center; gap:6px;">
						<input type="radio" name="assignment_mode" value="auto" {{ $assignmentMode === 'auto' ? 'checked' : '' }}>
						<span>Phân công tự động</span>
					</label>
				</div>
				@error('assignment_mode')
					<div class="form-error">{{ $message }}</div>
				@enderror
			</div>

			<div id="manual-assignment-section" style="display:none;">
				<input type="hidden" name="manual_target" value="both">

				<div class="form-grid-2">
					<div class="form-group">
						<details open>
							<summary class="form-label" style="cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between;">
								<span>Admin</span>
								<span aria-hidden="true">▾</span>
							</summary>
							<div style="margin-top:10px; border:1px solid #e5e7eb; border-radius:8px; padding:10px; max-height:260px; overflow:auto;">
								@forelse($managerUsers ?? [] as $manager)
									<label style="display:flex; align-items:center; gap:8px; margin:0 0 8px 0;">
										<input type="checkbox" data-manual-user="1" name="selected_manager_ids[]" value="{{ $manager->id }}" {{ in_array((string) $manager->id, $oldManagerIds, true) ? 'checked' : '' }}>
										<span>{{ $manager->ho_ten }}</span>
									</label>
								@empty
									<div class="empty-state" style="padding:8px 0;">Không có admin hoạt động để chọn.</div>
								@endforelse
							</div>
						</details>
						@error('selected_manager_ids')
							<div class="form-error">{{ $message }}</div>
						@enderror
						@error('selected_manager_ids.*')
							<div class="form-error">{{ $message }}</div>
						@enderror
					</div>

					<div class="form-group">
						<details open>
							<summary class="form-label" style="cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between;">
								<span>Nhân viên theo vị trí</span>
								<span aria-hidden="true">▾</span>
							</summary>
							<div style="margin-top:10px; border:1px solid #e5e7eb; border-radius:8px; padding:10px; max-height:360px; overflow:auto;">
								@forelse($staffPositionList as $positionName)
									@php
										$staffByPosition = ($staffUsersByPosition ?? collect())->get($positionName, collect());
									@endphp
									<details style="border:1px solid #edf2f7; border-radius:8px; padding:8px 10px; margin-bottom:8px;">
										<summary style="cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between; font-weight:600;">
											<span>{{ $positionName }}</span>
											<span class="text-muted">{{ $staffByPosition->count() }} người</span>
										</summary>
										<div style="margin-top:10px;">
											@foreach($staffByPosition as $staff)
												<label style="display:flex; align-items:center; gap:8px; margin:0 0 8px 0;">
													<input type="checkbox" data-manual-user="1" name="selected_staff_ids[]" value="{{ $staff->id }}" {{ in_array((string) $staff->id, $oldStaffIds, true) ? 'checked' : '' }}>
													<span>{{ $staff->ho_ten }}</span>
												</label>
											@endforeach
										</div>
									</details>
								@empty
									<div class="empty-state" style="padding:8px 0;">Chưa có dữ liệu vị trí nhân viên trong hồ sơ để phân ca.</div>
								@endforelse
							</div>
						</details>
						@error('selected_staff_ids')
							<div class="form-error">{{ $message }}</div>
						@enderror
						@error('selected_staff_ids.*')
							<div class="form-error">{{ $message }}</div>
						@enderror
					</div>
				</div>
			</div>

			<div id="auto-assignment-section" style="display:none;">
				<div class="form-group">
					<label style="display:flex; align-items:center; gap:8px; margin:0;">
						<input type="checkbox" id="auto-use-manager-count" name="auto_use_manager_count" value="1" {{ $useManagerCount ? 'checked' : '' }}>
						<span>Nhập số lượng admin</span>
					</label>
				</div>

				<div id="auto-manager-input-wrap" style="display:none; margin-bottom:14px;">
					<div class="form-group">
						<label class="form-label">Số lượng admin cần phân công</label>
						<input type="number" min="0" name="manager_count" id="manager-count-input" class="form-control" value="{{ old('manager_count', 0) }}">
						<div class="form-hint">Số admin hoạt động hiện có: {{ ($managerUsers ?? collect())->count() }}</div>
						@error('manager_count')
							<div class="form-error">{{ $message }}</div>
						@enderror
					</div>
				</div>

				<div class="form-group">
					<label style="display:flex; align-items:center; gap:8px; margin:0;">
						<input type="checkbox" id="auto-use-position-counts" name="auto_use_position_counts" value="1" {{ $usePositionCounts ? 'checked' : '' }}>
						<span>Nhập số lượng nhân viên theo vị trí</span>
					</label>
				</div>

				<div id="auto-position-input-wrap" style="display:none; margin-bottom:4px;">
					@forelse($staffPositionList as $positionName)
						@php
							$availableCount = (($staffUsersByPosition ?? collect())->get($positionName, collect()))->count();
							$positionKey = 'position_' . substr(md5($positionName), 0, 12);
							$oldCount = (int) old('position_counts.' . $positionKey, $oldPositionCounts->get($positionKey, 0));
						@endphp
						<input type="hidden" name="position_labels[{{ $positionKey }}]" value="{{ $positionName }}">
						<div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; border:1px solid #edf2f7; border-radius:8px; padding:10px;">
							<div>
								<div class="font-600">{{ $positionName }}</div>
								<div class="text-muted text-12">Hiện có {{ $availableCount }} nhân viên hoạt động</div>
							</div>
							<input type="number"
								   min="0"
								   name="position_counts[{{ $positionKey }}]"
								   class="form-control"
								   data-position-input="1"
								   style="width: 120px;"
								   value="{{ $oldCount }}">
						</div>
						@error('position_counts.' . $positionKey)
							<div class="form-error" style="margin-top:-2px; margin-bottom:10px;">{{ $message }}</div>
						@enderror
					@empty
						<div class="empty-state">Chưa có dữ liệu vị trí để nhập số lượng nhân viên.</div>
					@endforelse
				</div>
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
.time-spin-wrap {
	max-width: 220px;
}

.time-spin-fields {
	display: flex;
	align-items: center;
	gap: 8px;
}

.time-spin-input {
	max-width: 88px;
	text-align: center;
	padding-left: 8px;
	padding-right: 8px;
}

.time-spin-separator {
	font-weight: 700;
	color: var(--text-dark);
	font-size: 16px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
	const initializeTimeSpinInputs = () => {
		const parsePart = (rawValue, min, max) => {
			if (rawValue === null || rawValue === undefined || String(rawValue).trim() === '') {
				return null;
			}

			const parsed = Number.parseInt(String(rawValue), 10);
			if (Number.isNaN(parsed)) {
				return null;
			}

			return Math.min(max, Math.max(min, parsed));
		};

		Array.from(document.querySelectorAll('[data-time-spin]')).forEach((container) => {
			const hourInput = container.querySelector('[data-time-hour]');
			const minuteInput = container.querySelector('[data-time-minute]');
			const hiddenInput = container.querySelector('[data-time-hidden]');

			if (!hourInput || !minuteInput || !hiddenInput) {
				return;
			}

			const applyHiddenToVisible = () => {
				const matched = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(String(hiddenInput.value || '').trim());
				if (!matched) {
					return;
				}

				hourInput.value = matched[1];
				minuteInput.value = matched[2];
			};

			const syncVisibleToHidden = () => {
				const hourPart = parsePart(hourInput.value, 0, 23);
				const minutePart = parsePart(minuteInput.value, 0, 59);

				if (hourPart === null && minutePart === null) {
					hiddenInput.value = '';
					return;
				}

				const hh = String(hourPart ?? 0).padStart(2, '0');
				const mm = String(minutePart ?? 0).padStart(2, '0');

				hourInput.value = hh;
				minuteInput.value = mm;
				hiddenInput.value = `${hh}:${mm}`;
			};

			applyHiddenToVisible();
			syncVisibleToHidden();

			['input', 'change', 'blur'].forEach((eventName) => {
				hourInput.addEventListener(eventName, syncVisibleToHidden);
				minuteInput.addEventListener(eventName, syncVisibleToHidden);
			});
		});
	};

	initializeTimeSpinInputs();

	const assignmentRadios = Array.from(document.querySelectorAll('input[name="assignment_mode"]'));
	const manualSection = document.getElementById('manual-assignment-section');
	const autoSection = document.getElementById('auto-assignment-section');
	const manualCheckboxes = Array.from(document.querySelectorAll('input[data-manual-user="1"]'));

	const autoManagerToggle = document.getElementById('auto-use-manager-count');
	const autoManagerWrap = document.getElementById('auto-manager-input-wrap');
	const managerCountInput = document.getElementById('manager-count-input');

	const autoPositionToggle = document.getElementById('auto-use-position-counts');
	const autoPositionWrap = document.getElementById('auto-position-input-wrap');
	const positionInputs = Array.from(document.querySelectorAll('[data-position-input="1"]'));

	const toggleAutoDetailInputs = () => {
		const showManager = !!autoManagerToggle?.checked;
		const showPositions = !!autoPositionToggle?.checked;

		if (autoManagerWrap) {
			autoManagerWrap.style.display = showManager ? 'block' : 'none';
		}
		if (managerCountInput) {
			managerCountInput.disabled = !showManager;
			if (!showManager) {
				managerCountInput.value = '0';
			}
		}

		if (autoPositionWrap) {
			autoPositionWrap.style.display = showPositions ? 'block' : 'none';
		}
		positionInputs.forEach((input) => {
			input.disabled = !showPositions;
			if (!showPositions) {
				input.value = '0';
			}
		});
	};

	const toggleAssignmentSections = () => {
		const mode = assignmentRadios.find((radio) => radio.checked)?.value ?? 'manual';
		const isAuto = mode === 'auto';

		if (manualSection) {
			manualSection.style.display = isAuto ? 'none' : 'block';
		}
		if (autoSection) {
			autoSection.style.display = isAuto ? 'block' : 'none';
		}

		manualCheckboxes.forEach((checkbox) => {
			checkbox.disabled = isAuto;
			if (isAuto) {
				checkbox.checked = false;
			}
		});

		if (!isAuto) {
			if (autoManagerToggle) {
				autoManagerToggle.checked = false;
			}
			if (autoPositionToggle) {
				autoPositionToggle.checked = false;
			}
		}

		toggleAutoDetailInputs();
	};

	assignmentRadios.forEach((radio) => radio.addEventListener('change', toggleAssignmentSections));
	autoManagerToggle?.addEventListener('change', toggleAutoDetailInputs);
	autoPositionToggle?.addEventListener('change', toggleAutoDetailInputs);

	toggleAssignmentSections();
});
</script>
@endpush
