@extends('manager.layout.app')

@section('title', 'Quản lý ca làm việc')
@section('breadcrumb', 'Nhân sự / <strong>Quản lý ca làm việc</strong>')

@section('content')

<div class="page-header">
	<div>
		<h1 class="page-title">Quản lý ca làm việc</h1>
		<p class="page-subtitle">Danh sách ca làm theo khoảng ngày, có thể thêm, sửa, xóa và xem chi tiết</p>
	</div>
	<div class="page-actions">
		<a class="btn btn-primary" href="{{ route('manager.shifts.create') }}">Thêm ca làm việc</a>
	</div>
</div>

<div class="filter-bar">
	<form method="GET" action="{{ route('manager.shifts.index') }}" class="flex-gap-10">
		<input type="date" name="ngay_bat_dau" class="form-control" value="{{ $selectedStartDate }}" title="Ngày bắt đầu">
		<input type="date" name="ngay_ket_thuc" class="form-control" value="{{ $selectedEndDate }}" title="Ngày kết thúc">
		<input type="text" name="search" class="form-control filter-search"
			   placeholder="Tìm theo tên ca hoặc tên nhân sự..." value="{{ $search ?? request('search') }}">
		<button type="submit" class="btn btn-primary">Lọc</button>
		<a href="{{ route('manager.shifts.index') }}" class="btn btn-secondary">Xóa lọc</a>
	</form>
</div>

<div class="card">
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th class="col-stt">STT</th>
					<th>Ngày làm</th>
					<th>Tên ca</th>
					<th>Giờ bắt đầu</th>
					<th>Giờ kết thúc</th>
					<th>Thời lượng</th>
					<th>Số nhân viên</th>
					<th class="col-action-lg">Thao tác</th>
				</tr>
			</thead>
			<tbody>
				@php
					$currentTime = now();
				@endphp
				@forelse($shifts ?? [] as $i => $shift)
				@php
					$stt = method_exists($shifts, 'firstItem') && $shifts->firstItem()
						? ($shifts->firstItem() + $i)
						: ($i + 1);

					$shiftDate = $shift->ngay_lam
						? \Carbon\Carbon::parse($shift->ngay_lam)->format('Y-m-d')
						: $currentTime->format('Y-m-d');

					$start = \Carbon\Carbon::createFromFormat(
						'Y-m-d H:i:s',
						$shiftDate . ' ' . \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i:s')
					);
					$end = \Carbon\Carbon::createFromFormat(
						'Y-m-d H:i:s',
						$shiftDate . ' ' . \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i:s')
					);

					if ($end->lessThanOrEqualTo($start)) {
						$end->addDay();
					}

					$durationHours = $start->diffInMinutes($end) / 60;

					$statusClass = '';
					$statusText = 'Đã kết thúc';
					if ($currentTime->lt($start)) {
						$statusText = 'Chưa đến';
					} elseif ($currentTime->between($start, $end)) {
						$statusClass = 'shift-row-active';
						$statusText = 'Đang hoạt động';
					}
				@endphp
				<tr class="{{ $statusClass }}">
					<td>{{ $stt }}</td>
					<td>{{ $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—' }}</td>
					<td>
						<div class="font-600">{{ $shift->ten_ca }}</div>
						<div class="text-12 text-muted">{{ $statusText }}</div>
					</td>
					<td>{{ $start->format('H:i') }}</td>
					<td>{{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</td>
					<td>{{ number_format($durationHours, 2, ',', '.') }} giờ</td>
					<td>{{ number_format((int) ($shift->so_nhan_su ?? 0), 0, ',', '.') }}</td>
					<td>
						<div class="action-row">
							<a href="{{ route('manager.shifts.show', $shift->id) }}" class="btn btn-secondary btn-sm">Chi tiết</a>
							<a href="{{ route('manager.shifts.edit', ['id' => $shift->id, 'mode' => 'all']) }}" class="btn btn-warning btn-sm">Sửa</a>
							<form method="POST" action="{{ route('manager.shifts.destroy', $shift->id) }}"
								  onsubmit="return confirmDelete(this, 'Xóa toàn bộ nhân sự trong ca {{ addslashes($shift->ten_ca) }}?')">
								@csrf
								@method('DELETE')
								<button type="submit" class="btn btn-danger btn-sm">Xóa</button>
							</form>
						</div>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="8" class="empty-state">
						Chưa có ca làm việc nào. <a class="btn btn-link link-primary" href="{{ route('manager.shifts.create') }}">Thêm ngay</a>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@if(isset($shifts) && method_exists($shifts, 'hasPages') && $shifts->hasPages())
	<div class="card-footer">
		<div class="pagination-footer">
			<span class="pagination-info">
				Hiển thị {{ $shifts->firstItem() }}-{{ $shifts->lastItem() }} / {{ $shifts->total() }} ca làm
			</span>
			{{ $shifts->links() }}
		</div>
	</div>
	@endif
</div>

@endsection

@push('styles')
<style>
	.shift-row-active {
		background: var(--accent);
	}

	.shift-row-active:hover {
		background: var(--accent-dark) !important;
	}
</style>
@endpush
