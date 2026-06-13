@extends('staff.layout.app')
@section('title', 'Chi tiết ca làm việc')
@section('breadcrumb')
<a href="{{ route('staff.shifts.index') }}">Ca làm việc</a> / <strong>Chi tiết ca</strong>
@endsection

@section('content')

@php
    $now = now();
    $statusText = 'Đã kết thúc';
    if ($now->lessThan($start)) {
        $statusText = 'Chưa diễn ra';
    } elseif ($now->between($start, $end)) {
        $statusText = 'Đang diễn ra';
    }

    $attendance = $attendance ?? null;

    // Có thể check-in từ 15 phút trước giờ bắt đầu cho đến khi kết thúc ca
    $canCheckin = !$attendance && $now->greaterThanOrEqualTo($start->copy()->subMinutes(15)) && $now->lessThanOrEqualTo($end);

    // Có thể check-out miễn là đã check-in và chưa quá 15 phút sau giờ kết thúc
    $canCheckout = $attendance && !$attendance->cham_cong_ra && $now->lessThanOrEqualTo($end->copy()->addMinutes(15));

    $evalCheckin = '—';
    $evalCheckout = '—';
    
    if ($attendance && $attendance->cham_cong_vao) {
        $ciTime = \Carbon\Carbon::parse($attendance->cham_cong_vao);
        $diffMin = (int) round($ciTime->diffInSeconds($start, false) / 60);
        if ($diffMin > 0) {
            $evalCheckin = 'Sớm ' . app(\App\Services\ShiftService::class)->formatMinutesToHours($diffMin);
        } elseif ($diffMin < 0) {
            $evalCheckin = '<span style="color:#e74c3c;">Muộn ' . app(\App\Services\ShiftService::class)->formatMinutesToHours(abs($diffMin)) . '</span>';
        } else {
            $evalCheckin = 'Đúng giờ';
        }
    }

    if ($attendance && $attendance->cham_cong_ra) {
        $coTime = \Carbon\Carbon::parse($attendance->cham_cong_ra);
        $diffMin = (int) round($coTime->diffInSeconds($end, false) / 60);
        if ($diffMin > 0) {
            $evalCheckout = '<span style="color:#e74c3c;">Sớm ' . app(\App\Services\ShiftService::class)->formatMinutesToHours($diffMin) . '</span>';
        } elseif ($diffMin < 0) {
            $evalCheckout = 'Muộn ' . app(\App\Services\ShiftService::class)->formatMinutesToHours(abs($diffMin));
        } else {
            $evalCheckout = 'Đúng giờ';
        }
    }

    if ($statusText === 'Đã kết thúc' && !$attendance) {
        $evalCheckin = '<span class="text-danger font-600">Vắng mặt</span>';
        $evalCheckout = '<span class="text-danger font-600">Vắng mặt</span>';
    }
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Ca làm việc: {{ $shift->ten_ca }}</h1>
        <p class="page-subtitle">{{ optional($shift->ngay_lam)->format('d/m/Y') }} | {{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }} — {{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.shifts.index') }}" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Thông tin ca</span>
        @if($statusText === 'Đã kết thúc')
            <span class="badge badge-done">{{ $statusText }}</span>
        @elseif($statusText === 'Đang diễn ra')
            <span class="badge badge-active">{{ $statusText }}</span>
        @else
            <span class="badge badge-pending">{{ $statusText }}</span>
        @endif
    </div>
    <div class="card-body">
        <div class="form-grid-2">
            <div>
                <div class="text-12 text-muted">Tên ca</div>
                <div class="font-600">{{ $shift->ten_ca }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Ngày làm</div>
                <div class="font-600">{{ optional($shift->ngay_lam)->format('d/m/Y') }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Giờ bắt đầu</div>
                <div class="font-600">{{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Giờ kết thúc</div>
                <div class="font-600">{{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Chấm công vào lúc</div>
                <div class="font-600">{{ optional($attendance?->check_in_luc)->format('H:i d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Đánh giá chấm công vào</div>
                <div class="font-600">{!! $evalCheckin !!}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Chấm công ra lúc</div>
                <div class="font-600">{{ optional($attendance?->check_out_luc)->format('H:i d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Đánh giá chấm công ra</div>
                <div class="font-600">{!! $evalCheckout !!}</div>
            </div>
        </div>

        @if($canCheckin || $canCheckout)
            <div style="margin-top: 20px;">
                @if($canCheckin)
                    <form method="POST" action="{{ route('staff.shifts.checkin') }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="ca_lam_viec_id" value="{{ $shift->id }}">
                        <button type="submit" class="btn btn-success">Chấm công vào</button>
                    </form>
                @endif
                @if($canCheckout)
                    <form method="POST" action="{{ route('staff.shifts.checkout') }}" style="display:inline;"
                          onsubmit="return confirm('Xác nhận chấm công ra?')">
                        @csrf
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                        <button type="submit" class="btn btn-danger">Chấm công ra</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Đồng nghiệp cùng ca</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coworkers as $idx => $member)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="font-600">{{ $member->ho_ten ?? '—' }}</td>
                    <td>{{ $member->email ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="empty-state">Không có đồng nghiệp nào khác trong ca này.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
