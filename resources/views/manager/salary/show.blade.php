@extends('manager.layout.app')

@section('title', 'Chi tiết lương — ' . $user->ho_ten)
@section('breadcrumb', 'Kho & Tài chính / Chi tiêu / Lương / <strong>Chi tiết</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Chi tiết lương — {{ $user->ho_ten }}</h1>
        <p class="page-subtitle">Kỳ lương: {{ $periodStart->format('d/m/Y') }} → {{ $periodEnd->format('d/m/Y') }}</p>
    </div>
    <a href="{{ route('manager.salary.index', ['thang' => $thang, 'nam' => $nam]) }}" class="btn btn-secondary">← Quay lại</a>
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Vai trò</div>
        <div class="stat-value" style="font-size: 18px;">{{ ucfirst($user->vai_tro) }}</div>
        <div class="text-12 text-muted mt-6">{{ $salaryRow['loai_hinh'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng giờ làm</div>
        <div class="stat-value" style="font-size: 20px;">{{ $salaryRow['tong_gio_format'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng lương</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($salaryRow['tong_luong'], 0, ',', '.') }}đ</div>
    </div>
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Chức vụ</div>
        <div class="stat-value" style="font-size: 16px;">{{ $salaryRow['chuc_vu'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lương cơ bản</div>
        <div class="stat-value" style="font-size: 16px;">{{ $salaryRow['luong_co_ban'] !== null ? number_format($salaryRow['luong_co_ban'], 0, ',', '.') . 'đ' : '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lương theo giờ</div>
        <div class="stat-value" style="font-size: 16px;">{{ $salaryRow['luong_theo_gio'] !== null ? number_format($salaryRow['luong_theo_gio'], 0, ',', '.') . 'đ/giờ' : '—' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Chi tiết ca làm việc trong kỳ</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Ca</th>
                    <th>Giờ bắt đầu</th>
                    <th>Giờ kết thúc</th>
                    @if($user->vai_tro !== 'quản lý')
                        <th>Chấm công vào</th>
                        <th>Chấm công ra</th>
                    @endif
                    <th>Số giờ thực tế</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @if($user->vai_tro === 'quản lý')
                    @forelse($managerShifts as $shift)
                        @php
                            $start = \Carbon\Carbon::parse($shift->ngay_lam . ' ' . $shift->gio_bat_dau);
                            $end = \Carbon\Carbon::parse($shift->ngay_lam . ' ' . $shift->gio_ket_thuc);
                            if ($end->lessThanOrEqualTo($start)) {
                                $end->addDay();
                            }
                            $hours = round($start->diffInMinutes($end) / 60, 2);
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') }}</td>
                            <td><strong>{{ $shift->ten_ca ?? '—' }}</strong></td>
                            <td>{{ $shift->gio_bat_dau ?? '—' }}</td>
                            <td>{{ $shift->gio_ket_thuc ?? '—' }}</td>
                            <td>{{ $hours > 0 ? number_format($hours, 1) . ' giờ' : '—' }}</td>
                            <td class="text-muted">{{ $shift->ghi_chu ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Không có ca làm việc nào đã kết thúc trong kỳ này.</td>
                        </tr>
                    @endforelse
                @else
                    @forelse($attendances as $attendance)
                        @php
                            $shift = $attendance->caLamViec;
                            $checkIn = $attendance->cham_cong_vao ? \Carbon\Carbon::parse($attendance->cham_cong_vao) : null;
                            $checkOut = $attendance->cham_cong_ra ? \Carbon\Carbon::parse($attendance->cham_cong_ra) : null;
                            $hours = ($checkIn && $checkOut && $checkOut->greaterThan($checkIn))
                                ? round($checkIn->diffInMinutes($checkOut) / 60, 2)
                                : 0;
                        @endphp
                        <tr>
                            <td>{{ optional($shift)->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—' }}</td>
                            <td><strong>{{ $shift->ten_ca ?? '—' }}</strong></td>
                            <td>{{ $shift->gio_bat_dau ?? '—' }}</td>
                            <td>{{ $shift->gio_ket_thuc ?? '—' }}</td>
                            <td class="text-12">{{ $checkIn ? $checkIn->format('H:i d/m') : '—' }}</td>
                            <td class="text-12">{{ $checkOut ? $checkOut->format('H:i d/m') : '—' }}</td>
                            <td>{{ $hours > 0 ? number_format($hours, 1) . ' giờ' : '—' }}</td>
                            <td class="text-muted">{{ $attendance->ghi_chu ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">Không có dữ liệu chấm công trong kỳ này.</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
