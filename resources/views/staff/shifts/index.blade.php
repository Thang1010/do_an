@extends('staff.layout.app')
@section('title', 'Ca làm việc')
@section('breadcrumb')
Nhân viên / <strong>Ca làm việc</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Ca làm việc</h1>
        <p class="page-subtitle">Xem ca làm việc theo ngày và xem chi tiết</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.shifts.export', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-success" style="background-color: #27AE60; border-color: #27AE60; color: white; display: flex; align-items: center; gap: 4px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Xuất Excel
        </a>
    </div>
</div>
<div class="filter-bar">
    <form method="GET" action="/staff/shifts" class="flex-gap-10">
        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/staff/shifts" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Danh sách ca làm việc</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Tên ca</th>
                    <th>Ngày làm</th>
                    <th>Giờ ca</th>
                    <th>Trạng thái</th>
                    <th style="width: 240px; min-width: 240px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $i => $shift)
                @php
                    $now = now();
                    $shiftDate = $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('Y-m-d') : $now->toDateString();
                    $start = \Carbon\Carbon::parse($shiftDate . ' ' . $shift->gio_bat_dau);
                    $end = \Carbon\Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);
                    if ($end->lessThanOrEqualTo($start)) {
                        $end->addDay();
                    }

                    $statusLabel = 'Đã kết thúc';
                    $statusClass = 'badge-done';
                    if ($now->lessThan($start)) {
                        $statusLabel = 'Chưa diễn ra';
                        $statusClass = 'badge-pending';
                    } elseif ($now->between($start, $end)) {
                        $statusLabel = 'Đang diễn ra';
                        $statusClass = 'badge-active';
                    }

                    $attendance = $attendanceMap[$shift->id] ?? null;
                    
                    // Có thể check-in từ 15 phút trước giờ bắt đầu cho đến khi kết thúc ca
                    $canCheckin = !$attendance && $now->greaterThanOrEqualTo($start->copy()->subMinutes(15)) && $now->lessThanOrEqualTo($end);
                    
                    // Có thể check-out miễn là đã check-in và chưa quá 15 phút sau giờ kết thúc
                    $canCheckout = $attendance && !$attendance->cham_cong_ra && $now->lessThanOrEqualTo($end->copy()->addMinutes(15));
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="font-600">{{ $shift->ten_ca }}</td>
                    <td>{{ optional($shift->ngay_lam)->format('d/m/Y') }}</td>
                    <td class="text-muted">{{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }} — {{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td style="display: flex; gap: 8px;">
                        <a href="{{ route('staff.shifts.show', $shift->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                        @if($canCheckin)
                            <form method="POST" action="{{ route('staff.shifts.checkin') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="ca_lam_viec_id" value="{{ $shift->id }}">
                                <button type="submit" class="btn btn-success btn-sm">Chấm công vào</button>
                            </form>
                        @endif
                        @if($canCheckout)
                            <form method="POST" action="{{ route('staff.shifts.checkout') }}" style="display:inline;" onsubmit="return confirm('Xác nhận chấm công ra?')">
                                @csrf
                                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                                <button type="submit" class="btn btn-danger btn-sm">Chấm công ra</button>
                            </form>
                        @endif
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
