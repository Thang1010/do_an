@extends('manager.layout.app')

@section('title', 'Chi tiết ca làm việc')

@section('breadcrumb')
    Nhân sự / <a href="{{ route('manager.shifts.index') }}">Quản lý ca làm việc</a> / <strong>Chi tiết ca</strong>
@endsection

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $shift->ten_ca }}</h1>
            <p class="page-subtitle">Chi tiết ca và danh sách nhân sự trong ca</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-secondary" onclick="openModal('shift-checkin-qr-modal')">Tạo QR chấm
                công</button>
            <a href="{{ route('manager.shifts.index') }}" class="btn btn-primary">Quay lại</a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <span class="card-title">Thông tin ca làm việc</span>
            <a href="{{ route('manager.shifts.edit', ['id' => $shift->id, 'mode' => 'info']) }}"
                class="btn btn-warning btn-sm">Sửa thông tin ca</a>
        </div>
        <div class="card-body"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
            <div>
                <div class="text-12 text-muted">Ngày làm</div>
                <div class="font-600">
                    {{ $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Tên ca</div>
                <div class="font-600">{{ $shift->ten_ca }}</div>
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
                <div class="text-12 text-muted">Thời lượng</div>
                <div class="font-600">{{ number_format($shiftDurationHours, 2, ',', '.') }} giờ</div>
            </div>
            <div>
                <div class="text-12 text-muted">Số nhân viên làm ca đó</div>
                <div class="font-600">{{ number_format($totalAssignedUsers ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <span class="card-title">Danh sách nhân sự ca làm</span>
            <a href="{{ route('manager.shifts.edit', ['id' => $shift->id, 'mode' => 'staff']) }}"
                class="btn btn-warning btn-sm">Sửa nhân sự ca</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th>Email</th>
                        <th>Nhân sự</th>
                        <th>Vai trò</th>
                        <th>Chấm công vào</th>
                        <th>Chấm công ra</th>
                        <th>Số giờ</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memberDetails ?? [] as $i => $member)
                        @php
                            $stt = $i + 1;
                        @endphp
                        <tr>
                            <td>{{ $stt }}</td>
                            <td>{{ $member['email'] ?? '—' }}</td>
                            <td>
                                <div class="font-600">{{ $member['nhan_su'] ?? '—' }}</div>
                                <div class="text-12 text-muted">{{ $member['ma_nhan_vien'] ?? 'Không có mã' }}</div>
                            </td>
                            <td>{{ $member['vai_tro'] ?? '—' }}</td>
                            <td>{{ $member['check_in'] ? $member['check_in']->format('d/m H:i') : '—' }}</td>
                            <td>{{ $member['check_out'] ? $member['check_out']->format('d/m H:i') : '—' }}</td>
                            <td>{{ number_format((float) ($member['so_gio'] ?? 0), 2, ',', '.') }} giờ</td>
                            <td>{{ \Illuminate\Support\Str::limit($member['ghi_chu'] ?? '—', 100) }}</td>
                                                <td>
                                <div class="action-row" style="display: flex; gap: 8px; justify-content: center;">
                                    @if(isset($isShiftActive) && $isShiftActive)
                                        @if($member['can_force_checkin'] ?? false)
                                            <form method="POST" action="{{ route('manager.shifts.force-checkin', ['id' => $shift->id, 'userId' => $member['nguoi_dung_id']]) }}"
                                                  onsubmit="return confirmSubmit(this, 'Xác nhận chấm công vào hộ cho nhân sự này?', { title: 'Chấm công vào hộ', okText: 'Chấm công vào' })">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Chấm công vào</button>
                                            </form>
                                        @elseif($member['can_force_checkout'] ?? false)
                                            <form method="POST" action="{{ route('manager.shifts.force-checkout', ['id' => $shift->id, 'userId' => $member['nguoi_dung_id']]) }}"
                                                  onsubmit="return confirmSubmit(this, 'Xác nhận kết thúc ca hộ cho nhân sự này?', { title: 'Chấm công ra hộ', okText: 'Chấm công ra' })">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm">Chấm công ra</button>
                                            </form>
                                        @else
                                            <a href="{{ route('manager.users.show', $member['nguoi_dung_id']) }}" class="btn btn-secondary btn-sm">Chi tiết</a>
                                        @endif
                                    @else
                                        <a href="{{ route('manager.users.show', $member['nguoi_dung_id']) }}" class="btn btn-secondary btn-sm">Chi tiết</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">Ca làm việc này chưa có nhân sự được phân công.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-backdrop" id="shift-checkin-qr-modal">
        <div class="modal-box modal-md">
            <div class="modal-header">
                <span class="modal-title">QR chấm công ca {{ $shift->ten_ca }}</span>
                <button class="modal-close" onclick="closeModal('shift-checkin-qr-modal')">&#x2715;</button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; align-items:center; gap:12px;">
                <p class="text-12 text-muted" style="text-align:center; margin:0;">
                    Quét QR để chấm công vào và ra ca. Quét lần 1 → vào ca, quét lần 2 → ra ca.
                </p>
                <img src="{{ $checkinQrImageUrl }}" alt="QR chấm công"
                     style="display:block; width:280px; max-width:100%; height:auto;
                            border:1px solid rgba(240,221,184,0.25); border-radius:12px;
                            padding:12px; background:#fff;">
                <div style="width:100%;">
                    <label class="form-label" style="font-size:12px;">Link chấm công</label>
                    <input type="text" class="form-control" value="{{ $checkinQrUrl }}" readonly onclick="this.select()">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('shift-checkin-qr-modal')">Đóng</button>
            </div>
        </div>
    </div>

@endsection
