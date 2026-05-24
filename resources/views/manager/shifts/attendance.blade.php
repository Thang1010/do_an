@extends('manager.layout.app')

@section('title', 'Chấm công')
@section('breadcrumb', 'Nhân sự / <strong>Chấm công</strong>')

@section('content')

<div class="page-header">
	<div>
		<h1 class="page-title">Chấm công theo ca làm việc</h1>
		<p class="page-subtitle">Lọc theo ngày, tên nhân sự, ca làm việc và xuất bảng lương theo dữ liệu chấm công</p>
	</div>
	<div class="page-actions">
		<a href="{{ route('manager.shifts.attendance.export-payroll', ['ngay' => $selectedDate, 'nhan_vien' => $employeeKeyword, 'ca_lam_viec_id' => $selectedShiftId]) }}" class="btn btn-secondary">
			Xuất bảng lương
		</a>
		<button class="btn btn-primary" onclick="openModal('create-attendance-modal')">Thêm chấm công</button>
	</div>
</div>

<div class="filter-bar">
	<form method="GET" action="{{ route('manager.shifts.attendance') }}" class="flex-gap-10">
		<input type="date" name="ngay" class="form-control" value="{{ $selectedDate }}">
		<input type="text" name="nhan_vien" class="form-control filter-search"
			   placeholder="Lọc theo tên nhân sự..." value="{{ $employeeKeyword }}">
		<select name="ca_lam_viec_id" class="form-control">
			<option value="">Tất cả ca làm việc</option>
			@foreach($shifts ?? [] as $shift)
				<option value="{{ $shift->id }}" {{ (string) $selectedShiftId === (string) $shift->id ? 'selected' : '' }}>
					{{ $shift->ten_ca }} - {{ $shift->nguoiDung?->ho_ten ?? 'Không rõ' }} - {{ $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—' }} ({{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }})
				</option>
			@endforeach
		</select>
		<button type="submit" class="btn btn-primary">Lọc</button>
		<a href="{{ route('manager.shifts.attendance') }}" class="btn btn-secondary">Xóa lọc</a>
	</form>
</div>

<div class="card">
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th class="col-stt">STT</th>
					<th>Ngày làm</th>
					<th>Nhân viên</th>
					<th>Ca làm việc</th>
					<th>Check-in / Check-out</th>
					<th>Số giờ</th>
					<th>Ghi chú</th>
					<th class="col-action-xl">Thao tác</th>
				</tr>
			</thead>
			<tbody>
				@forelse($attendanceRecords ?? [] as $i => $record)
				@php
					$stt = method_exists($attendanceRecords, 'firstItem') && $attendanceRecords->firstItem()
						? ($attendanceRecords->firstItem() + $i)
						: ($i + 1);

					$shift = $record->caLamViec;
					$user = $record->nguoiDung ?? $shift?->nguoiDung;
					$checkIn = $record->check_in_luc ? \Carbon\Carbon::parse($record->check_in_luc) : null;
					$checkOut = $record->check_out_luc ? \Carbon\Carbon::parse($record->check_out_luc) : null;

					if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
						$hours = $checkIn->diffInMinutes($checkOut) / 60;
					} else {
						$hours = 0;
						if ($shift) {
							$start = \Carbon\Carbon::parse($shift->gio_bat_dau);
							$end = \Carbon\Carbon::parse($shift->gio_ket_thuc);
							if ($end->lessThanOrEqualTo($start)) {
								$end->addDay();
							}
							$hours = $start->diffInMinutes($end) / 60;
						}
					}
				@endphp
				<tr>
					<td>{{ $stt }}</td>
					<td>{{ $shift?->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—' }}</td>
					<td>
						<div class="font-600">{{ $user->ho_ten ?? '—' }}</div>
						<div class="text-12 text-muted">{{ $user?->hoSoNhanVien?->ma_nhan_vien ?? 'Không có mã' }}</div>
					</td>
					<td>
						@if($shift)
							<div class="font-600">{{ $shift->ten_ca }}</div>
							<div class="text-12 text-muted">{{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</div>
						@else
							—
						@endif
					</td>
					<td>
						<div class="text-13">{{ $checkIn ? $checkIn->format('d/m H:i') : '—' }}</div>
						<div class="text-12 text-muted">{{ $checkOut ? $checkOut->format('d/m H:i') : '—' }}</div>
					</td>
					<td>{{ number_format($hours, 2, ',', '.') }} giờ</td>
					<td>{{ \Illuminate\Support\Str::limit($record->ghi_chu ?? '—', 40) }}</td>
					<td>
						@php
							$deleteMessage = 'Xóa bản ghi chấm công của ' . ($user->ho_ten ?? 'nhân sự') . '?';
						@endphp
						<div class="action-row">
							<button type="button" class="btn btn-warning btn-sm"
									onclick="openModal('edit-attendance-modal-{{ $record->id }}')">
								Sửa
							</button>
							<form method="POST" action="{{ route('manager.shifts.attendance.destroy', $record->id) }}"
								  onsubmit="return confirmDelete(this, '{{ addslashes($deleteMessage) }}')">
								@csrf
								@method('DELETE')
								<input type="hidden" name="ngay" value="{{ $selectedDate }}">
								<input type="hidden" name="nhan_vien" value="{{ $employeeKeyword }}">
								<input type="hidden" name="ca_lam_viec_id" value="{{ $selectedShiftId }}">
								<button type="submit" class="btn btn-danger btn-sm">Xóa</button>
							</form>
						</div>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="8" class="empty-state">Không có bản ghi chấm công phù hợp bộ lọc.</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@if(isset($attendanceRecords) && method_exists($attendanceRecords, 'hasPages') && $attendanceRecords->hasPages())
	<div class="card-footer">
		<div class="pagination-footer">
			<span class="pagination-info">
				Hiển thị {{ $attendanceRecords->firstItem() }}-{{ $attendanceRecords->lastItem() }} / {{ $attendanceRecords->total() }} bản ghi
			</span>
			{{ $attendanceRecords->links() }}
		</div>
	</div>
	@endif
</div>

<div class="modal-backdrop" id="create-attendance-modal">
	<div class="modal-box modal-md">
		<div class="modal-header">
			<span class="modal-title">Thêm chấm công</span>
			<button class="modal-close" onclick="closeModal('create-attendance-modal')">&#x2715;</button>
		</div>
		<div class="modal-body">
			<form id="create-attendance-form" method="POST" action="{{ route('manager.shifts.attendance.store') }}">
				@csrf
				<input type="hidden" name="form_type" value="create_attendance">
				<input type="hidden" name="ngay" value="{{ $selectedDate }}">
				<input type="hidden" name="nhan_vien" value="{{ $employeeKeyword }}">
				<input type="hidden" name="ca_lam_viec_id" value="{{ $selectedShiftId }}">

				<div class="form-group">
					<label class="form-label">Chọn ca làm việc <span>*</span></label>
					<select name="ca_lam_viec_id" class="form-control" required>
						<option value="">-- Chọn ca --</option>
						@foreach($shiftsForAttendance ?? [] as $shift)
							<option value="{{ $shift->id }}" {{ (string) old('ca_lam_viec_id') === (string) $shift->id ? 'selected' : '' }}>
								{{ $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : 'Không rõ ngày' }} - {{ $shift->nguoiDung?->ho_ten ?? 'Không rõ' }} - {{ $shift->ten_ca }} ({{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }})
							</option>
						@endforeach
					</select>
				</div>

				<div class="form-group">
					<label class="form-label">Check-in</label>
					<input type="datetime-local" name="check_in_luc" class="form-control"
						   value="{{ old('check_in_luc') ? \Carbon\Carbon::parse(old('check_in_luc'))->format('Y-m-d\TH:i') : '' }}">
				</div>

				<div class="form-group">
					<label class="form-label">Check-out</label>
					<input type="datetime-local" name="check_out_luc" class="form-control"
						   value="{{ old('check_out_luc') ? \Carbon\Carbon::parse(old('check_out_luc'))->format('Y-m-d\TH:i') : '' }}">
				</div>

				<div class="form-group">
					<label class="form-label">Ghi chú</label>
					<textarea name="ghi_chu" class="form-control" rows="3" maxlength="500">{{ old('ghi_chu') }}</textarea>
				</div>
			</form>
			@if(($shiftsForAttendance ?? collect())->isEmpty())
				<div class="text-12 text-muted">Không có ca làm việc phù hợp với bộ lọc hiện tại để chấm công.</div>
			@endif
		</div>
		<div class="modal-footer">
			<button class="btn btn-secondary" onclick="closeModal('create-attendance-modal')">Hủy</button>
			<button class="btn btn-primary" onclick="document.getElementById('create-attendance-form').submit()">Lưu</button>
		</div>
	</div>
</div>

@foreach($attendanceRecords ?? [] as $record)
<div class="modal-backdrop" id="edit-attendance-modal-{{ $record->id }}">
	<div class="modal-box modal-md">
		<div class="modal-header">
			<span class="modal-title">Sửa chấm công</span>
			<button class="modal-close" onclick="closeModal('edit-attendance-modal-{{ $record->id }}')">&#x2715;</button>
		</div>
		<div class="modal-body">
			<form id="edit-attendance-form-{{ $record->id }}" method="POST" action="{{ route('manager.shifts.attendance.update', $record->id) }}">
				@csrf
				@method('PUT')
				<input type="hidden" name="form_type" value="edit_attendance">
				<input type="hidden" name="attendance_id" value="{{ $record->id }}">
				<input type="hidden" name="ngay" value="{{ $selectedDate }}">
				<input type="hidden" name="nhan_vien" value="{{ $employeeKeyword }}">
				<input type="hidden" name="ca_lam_viec_id" value="{{ $selectedShiftId }}">

				<div class="form-group">
					<label class="form-label">Nhân sự</label>
					<input type="text" class="form-control" value="{{ $record->nguoiDung?->ho_ten ?? ($record->caLamViec?->nguoiDung?->ho_ten ?? '—') }}" disabled>
				</div>

				<div class="form-group">
					<label class="form-label">Check-in</label>
					<input type="datetime-local" name="check_in_luc" class="form-control"
						   value="{{ old('attendance_id') == $record->id ? (old('check_in_luc') ? \Carbon\Carbon::parse(old('check_in_luc'))->format('Y-m-d\TH:i') : '') : (optional($record->check_in_luc) ? \Carbon\Carbon::parse($record->check_in_luc)->format('Y-m-d\TH:i') : '') }}">
				</div>

				<div class="form-group">
					<label class="form-label">Check-out</label>
					<input type="datetime-local" name="check_out_luc" class="form-control"
						   value="{{ old('attendance_id') == $record->id ? (old('check_out_luc') ? \Carbon\Carbon::parse(old('check_out_luc'))->format('Y-m-d\TH:i') : '') : (optional($record->check_out_luc) ? \Carbon\Carbon::parse($record->check_out_luc)->format('Y-m-d\TH:i') : '') }}">
				</div>

				<div class="form-group">
					<label class="form-label">Ghi chú</label>
					<textarea name="ghi_chu" class="form-control" rows="3" maxlength="500">{{ old('attendance_id') == $record->id ? old('ghi_chu') : ($record->ghi_chu ?? '') }}</textarea>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button class="btn btn-secondary" onclick="closeModal('edit-attendance-modal-{{ $record->id }}')">Hủy</button>
			<button class="btn btn-primary" onclick="document.getElementById('edit-attendance-form-{{ $record->id }}').submit()">Cập nhật</button>
		</div>
	</div>
</div>
@endforeach

@endsection

@push('scripts')
<div id="attendance-modal-state"
	 data-form-type="{{ old('form_type') }}"
	 data-attendance-id="{{ old('attendance_id') }}"
	 hidden></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
	const stateEl = document.getElementById('attendance-modal-state');
	const formType = stateEl ? stateEl.dataset.formType : '';
	const attendanceId = stateEl ? stateEl.dataset.attendanceId : '';

	if (formType === 'create_attendance') {
		openModal('create-attendance-modal');
		return;
	}

	if (formType === 'edit_attendance' && attendanceId) {
		openModal(`edit-attendance-modal-${attendanceId}`);
	}
});
</script>
@endpush
