@extends('staff.layout.app')
@section('title', 'Ca làm việc')
@section('breadcrumb', 'Quản lý / <strong>Ca làm việc</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Ca làm việc</h1>
        <p class="page-subtitle">Xem ca làm việc theo ngày và xem chi tiết</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.shifts.export', ['date' => $date]) }}" class="btn btn-secondary">Xuất Excel</a>
    </div>
</div>
<div class="filter-bar">
    <form method="GET" action="/staff/shifts" class="flex-gap-10">
        <input type="date" name="date" class="form-control" value="{{ $date }}">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/staff/shifts" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Danh sách ca làm việc</span>
        <span class="text-12 text-muted">{{ $shifts->count() }} ca</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Tên ca</th>
                    <th>Ngày làm</th>
                    <th>Giờ ca</th>
                    <th>Chấm công</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $i => $shift)
                @php
                    $attendance = $attendanceMap[$shift->id] ?? null;
                    $statusLabel = 'Chưa check-in';
                    $statusClass = 'badge-pending';
                    if ($attendance && $attendance->check_out_luc) {
                        $statusLabel = 'Đã check-out';
                        $statusClass = 'badge-done';
                    } elseif ($attendance && !$attendance->check_out_luc) {
                        $statusLabel = 'Đang làm việc';
                        $statusClass = 'badge-active';
                    }
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="font-600">{{ $shift->ten_ca }}</td>
                    <td>{{ optional($shift->ngay_lam)->format('d/m/Y') }}</td>
                    <td class="text-muted">{{ $shift->gio_bat_dau }} — {{ $shift->gio_ket_thuc }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td>
                        <a href="{{ route('staff.shifts.show', $shift->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">Không có ca làm việc cho ngày đã chọn.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
