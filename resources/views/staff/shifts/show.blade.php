@extends('staff.layout.app')
@section('title', 'Chi tiết ca làm việc')
@section('breadcrumb')
<a href="{{ route('staff.shifts.index') }}">Ca làm việc</a> / <strong>Chi tiết ca</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Ca làm việc: {{ $shift->ten_ca }}</h1>
        <p class="page-subtitle">{{ optional($shift->ngay_lam)->format('d/m/Y') }} | {{ $shift->gio_bat_dau }} — {{ $shift->gio_ket_thuc }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.shifts.index') }}" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Thông tin ca</span>
        @if($attendance && $attendance->check_out_luc)
            <span class="badge badge-done">Đã check-out</span>
        @elseif($attendance)
            <span class="badge badge-active">Đang làm việc</span>
        @else
            <span class="badge badge-pending">Chưa check-in</span>
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
                <div class="font-600">{{ $shift->gio_bat_dau }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Giờ kết thúc</div>
                <div class="font-600">{{ $shift->gio_ket_thuc }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Check-in lúc</div>
                <div class="font-600">{{ optional($attendance?->check_in_luc)->format('H:i d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Check-out lúc</div>
                <div class="font-600">{{ optional($attendance?->check_out_luc)->format('H:i d/m/Y') ?? 'Chưa check-out' }}</div>
            </div>
        </div>

        @if(!$isInShiftTime)
            <div class="alert alert-warning" style="margin-top: 16px;">
                Hiện chưa đến thời gian ca làm việc.
            </div>
        @endif

        <div style="margin-top: 16px;">
            @if($canCheckin)
                <form method="POST" action="{{ route('staff.shifts.checkin') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="ca_lam_viec_id" value="{{ $shift->id }}">
                    <button type="submit" class="btn btn-success">Check-in</button>
                </form>
            @elseif($canCheckout)
                <form method="POST" action="{{ route('staff.shifts.checkout') }}" style="display:inline;"
                      onsubmit="return confirm('Xác nhận check-out?')">
                    @csrf
                    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                    <button type="submit" class="btn btn-danger">Check-out</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
