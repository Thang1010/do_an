@extends('manager.layout.app')

@section('title', 'Sửa ca làm việc')
@section('breadcrumb', 'Nhân sự / Quản lý ca làm việc / <strong>Sửa ca</strong>')

@section('content')

@php
    $mode = old('edit_mode', $editMode ?? 'all');
    if (!in_array($mode, ['all', 'info', 'staff'], true)) {
        $mode = 'all';
    }

    $isAllMode = $mode === 'all';
    $isInfoMode = $mode === 'info';
    $isStaffMode = $mode === 'staff';

    $defaultManagerIds = is_array($assignedManagerIds ?? null)
        ? ($assignedManagerIds ?? [])
        : collect($assignedManagerIds ?? [])->all();

    $defaultStaffIds = is_array($assignedStaffIds ?? null)
        ? ($assignedStaffIds ?? [])
        : collect($assignedStaffIds ?? [])->all();

    $oldManagerIds = collect(old('selected_manager_ids', $defaultManagerIds))
        ->flatten(1)
        ->filter(fn($id) => is_scalar($id) && trim((string) $id) !== '')
        ->map(fn($id) => (string) $id)
        ->all();

    $oldStaffIds = collect(old('selected_staff_ids', $defaultStaffIds))
        ->flatten(1)
        ->filter(fn($id) => is_scalar($id) && trim((string) $id) !== '')
        ->map(fn($id) => (string) $id)
        ->all();

    $staffPositionList = collect($staffPositions ?? [])
        ->filter(fn($name) => is_scalar($name) && trim((string) $name) !== '')
        ->values();

    $subtitleMap = [
        'all' => 'Sửa toàn bộ thông tin ca và danh sách nhân sự như trang thêm',
        'info' => 'Chỉ sửa thông tin ca làm việc',
        'staff' => 'Chỉ sửa danh sách nhân sự của ca làm',
    ];

    $submitLabelMap = [
        'all' => 'Cập nhật đầy đủ ca làm',
        'info' => 'Cập nhật thông tin ca',
        'staff' => 'Cập nhật nhân sự ca',
    ];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa ca làm việc</h1>
        <p class="page-subtitle">{{ $subtitleMap[$mode] ?? $subtitleMap['all'] }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.shifts.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

<div class="card" style="max-width: 1080px;">
    <div class="card-header" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
        <span class="card-title">Thông tin ca làm việc</span>
        <span class="text-12 text-muted">Số nhân sự trong ca: {{ number_format((int) ($totalAssignedUsers ?? 0), 0, ',', '.') }}</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('manager.shifts.update', $shift->id) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_mode" value="{{ $mode }}">

            @if($isAllMode || $isInfoMode)
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Tên ca <span>*</span></label>
                        <input type="text" name="ten_ca" class="form-control" maxlength="100"
                               value="{{ old('ten_ca', $shift->ten_ca) }}" required>
                        @error('ten_ca')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ngày làm <span>*</span></label>
                        <input type="date" name="ngay_lam" class="form-control"
                               value="{{ old('ngay_lam', $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('Y-m-d') : '') }}" required>
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
                            <input type="hidden" name="gio_bat_dau" value="{{ old('gio_bat_dau', \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i')) }}" data-time-hidden>
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
                            <input type="hidden" name="gio_ket_thuc" value="{{ old('gio_ket_thuc', \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i')) }}" data-time-hidden>
                        </div>
                        @error('gio_ket_thuc')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            @if($isAllMode || $isStaffMode)
                <input type="hidden" name="assignment_mode" value="manual">
                <input type="hidden" name="manual_target" value="both">

                <div class="form-group">
                    <label class="form-label">Phân công thủ công <span>*</span></label>
                    <div class="form-hint" style="margin-bottom: 10px;">
                        Chọn lại danh sách admin và nhân viên làm trong ca này.
                    </div>
                    @error('assignment_mode')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <details open>
                        <summary class="form-label" style="cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between;">
                            <span>Admin</span>
                            <span aria-hidden="true">▾</span>
                        </summary>
                        <div style="margin-top:10px; border:1px solid #e5e7eb; border-radius:8px; padding:10px; max-height:260px; overflow:auto;">
                            @forelse($managerUsers ?? [] as $manager)
                                <label style="display:flex; align-items:center; gap:8px; margin:0 0 8px 0;">
                                    <input type="checkbox" name="selected_manager_ids[]" value="{{ $manager->id }}" {{ in_array((string) $manager->id, $oldManagerIds, true) ? 'checked' : '' }}>
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
                                                <input type="checkbox" name="selected_staff_ids[]" value="{{ $staff->id }}" {{ in_array((string) $staff->id, $oldStaffIds, true) ? 'checked' : '' }}>
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
            @endif

            <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-top:20px; padding-top:16px; border-top:1px solid var(--border); padding-right:6px; padding-bottom:4px;">
                <a href="{{ route('manager.shifts.index') }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">{{ $submitLabelMap[$mode] ?? $submitLabelMap['all'] }}</button>
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
});
</script>
@endpush
