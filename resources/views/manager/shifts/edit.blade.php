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

    $assignedCombined = collect($defaultManagerIds)->merge($defaultStaffIds)
        ->filter(fn($id) => is_scalar($id) && trim((string) $id) !== '')
        ->map(fn($id) => (string) $id)
        ->unique()
        ->values()
        ->all();

    $oldSelected = collect(old('selected_user_ids', $assignedCombined))
        ->flatten(1)
        ->filter(fn($id) => is_scalar($id) && trim((string) $id) !== '')
        ->map(fn($id) => (string) $id)
        ->unique()
        ->values()
        ->all();

    $shiftDateValue = old('ngay_lam', $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('Y-m-d') : '');
    $shiftStartValue = old('gio_bat_dau', \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i'));
    $shiftEndValue = old('gio_ket_thuc', \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i'));

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
        <a href="{{ route('manager.shifts.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="card" style="max-width: 1080px;">
    <div class="card-header" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
        <span class="card-title">Thông tin ca làm việc</span>
        <span class="text-12 text-muted">Số nhân sự trong ca: {{ number_format((int) ($totalAssignedUsers ?? 0), 0, ',', '.') }}</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('manager.shifts.update', $shift->id) }}"
              id="edit-shift-form"
              data-available-url="{{ route('manager.shifts.available-users') }}"
              data-shift-id="{{ $shift->id }}"
              data-shift-date="{{ $shiftDateValue }}"
              data-shift-start="{{ $shiftStartValue }}"
              data-shift-end="{{ $shiftEndValue }}"
              data-old-selected='@json($oldSelected)'>
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
                        <input type="date" name="ngay_lam" id="shift-date-input" class="form-control"
                               value="{{ $shiftDateValue }}" required>
                        @error('ngay_lam')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Giờ bắt đầu <span>*</span></label>
                        <input type="time" name="gio_bat_dau" class="form-control" value="{{ $shiftStartValue }}" required>
                        @error('gio_bat_dau')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giờ kết thúc <span>*</span></label>
                        <input type="time" name="gio_ket_thuc" class="form-control" value="{{ $shiftEndValue }}" required>
                        @error('gio_ket_thuc')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            @if($isAllMode || $isStaffMode)
                <div class="form-group">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <label class="form-label" style="margin:0;">Nhân sự rảnh <span>*</span></label>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-load-staff">Lấy danh sách nhân sự</button>
                    </div>
                    <div class="form-hint" style="margin:6px 0 0;">Nhân sự đang thuộc ca này được tick sẵn. Bấm lại nút nếu đổi ngày/giờ.</div>
                    <div id="available-users-box" style="margin-top:10px; border:1px solid #e5e7eb; border-radius:8px; padding:14px; max-height:420px; overflow:auto;">
                        <div class="empty-state" id="available-users-placeholder" style="padding:8px 0;">Đang tải danh sách nhân sự...</div>
                        <div id="available-users-list" style="display:none;"></div>
                    </div>
                    @error('selected_user_ids')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    @error('selected_user_ids.*')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
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

.role-group { border: 1px solid #edf2f7; border-radius: 8px; margin-bottom: 10px; }
.role-group > summary {
    cursor: pointer; user-select: none; list-style: none;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; padding: 10px 12px; font-weight: 600;
}
.role-group > summary::-webkit-details-marker { display: none; }
.role-group > summary .role-meta { font-weight: 500; font-size: 12px; color: #8a6d3b; }
.role-group[data-loai="0"] > summary { background: #f4ecdd; }
.role-group[data-loai="1"] > summary { background: #eef5ff; }
.role-group[data-loai="2"] > summary { background: #f6f6f6; color: #6b7280; }
.role-group__body { padding: 6px 12px 12px; }
.available-user-item { display: flex; align-items: center; gap: 10px; padding: 7px 4px; }
.available-user-item + .available-user-item { border-top: 1px solid #f4f1ec; }
.available-user-item.has-warn { background: #fff5f5; border-radius: 6px; }
.au-name { flex: 1; }
.lh-badge { font-size: 11px; padding: 1px 8px; border-radius: 999px; font-weight: 600; white-space: nowrap; }
.lh-badge.lh-ft { background: #e0edff; color: #1d4ed8; }
.lh-badge.lh-pt { background: #fff3d6; color: #92660a; }
.au-week { font-size: 12px; color: #6b7280; white-space: nowrap; }
.au-week.is-warn { color: #c0392b; font-weight: 600; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('edit-shift-form');
    if (!form) return;
    const dateInput = document.getElementById('shift-date-input');
    const listEl = document.getElementById('available-users-list');
    const placeholderEl = document.getElementById('available-users-placeholder');
    const loadBtn = document.getElementById('btn-load-staff');
    const availableUrl = form.dataset.availableUrl;
    const shiftId = form.dataset.shiftId;
    let oldSelected = [];
    try { oldSelected = JSON.parse(form.dataset.oldSelected || '[]').map(String); } catch (e) { oldSelected = []; }

    // ── Lấy danh sách nhân sự ──
    function getValues() {
        const startEl = document.querySelector('input[name="gio_bat_dau"]');
        const endEl = document.querySelector('input[name="gio_ket_thuc"]');
        const start = (startEl ? startEl.value : '') || form.dataset.shiftStart || '';
        const end = (endEl ? endEl.value : '') || form.dataset.shiftEnd || '';
        const date = (dateInput ? dateInput.value : '') || form.dataset.shiftDate || '';
        return { date, start, end };
    }

    function checkedIds() {
        return Array.from(listEl.querySelectorAll('input[name="selected_user_ids[]"]:checked')).map((c) => c.value);
    }

    function showPlaceholder(html) {
        placeholderEl.innerHTML = html;
        placeholderEl.style.display = 'block';
        listEl.style.display = 'none';
        listEl.innerHTML = '';
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, (s) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[s]));
    }

    function fmtH(value) {
        return String(Math.round((Number(value) || 0) * 100) / 100);
    }

    function userItemHtml(u, checked) {
        let badge = '';
        if (u.loai_hinh === 'bán thời gian') badge = '<span class="lh-badge lh-pt">Bán TG</span>';
        else if (u.loai_hinh === 'toàn thời gian') badge = '<span class="lh-badge lh-ft">Toàn TG</span>';
        const weekText = u.canh_bao
            ? `<span class="au-week is-warn" title="${escapeHtml(u.canh_bao_text)}">⚠ ${escapeHtml(u.canh_bao_text)}</span>`
            : `<span class="au-week">đã xếp ${fmtH(u.gio_tuan_hien_tai)}h/tuần</span>`;
        return `<label class="available-user-item${u.canh_bao ? ' has-warn' : ''}">
            <input type="checkbox" name="selected_user_ids[]" value="${u.id}" ${checked}>
            <span class="au-name">${escapeHtml(u.ho_ten)}</span>
            ${badge}
            ${weekText}
        </label>`;
    }

    async function loadStaff() {
        const { date, start, end } = getValues();
        if (!date || !start || !end) {
            showPlaceholder('Vui lòng điền đầy đủ <strong>ngày, giờ bắt đầu và giờ kết thúc</strong> trước.');
            return;
        }
        if (start === end) {
            showPlaceholder('Giờ bắt đầu và giờ kết thúc không được trùng nhau.');
            return;
        }

        const keep = new Set(checkedIds().concat(oldSelected));

        if (loadBtn) { loadBtn.disabled = true; loadBtn.dataset.label = loadBtn.textContent; loadBtn.textContent = 'Đang tải...'; }

        const params = new URLSearchParams({ ngay_lam: date, gio_bat_dau: start, gio_ket_thuc: end });
        if (shiftId) params.set('exclude_shift_id', shiftId);

        try {
            const res = await fetch(`${availableUrl}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!res.ok) { showPlaceholder('Không tải được danh sách nhân viên rảnh.'); return; }
            const data = await res.json();
            renderGroups(data.groups || [], keep);
        } catch (e) {
            showPlaceholder('Không tải được danh sách nhân viên rảnh.');
        } finally {
            if (loadBtn) { loadBtn.disabled = false; loadBtn.textContent = loadBtn.dataset.label || 'Lấy danh sách nhân sự'; }
        }
    }

    function renderGroups(groups, keep) {
        const totalUsers = groups.reduce((sum, g) => sum + (g.users ? g.users.length : 0), 0);
        if (!totalUsers) {
            showPlaceholder('Không có nhân viên nào rảnh trong khung giờ này.');
            return;
        }

        placeholderEl.style.display = 'none';
        listEl.style.display = 'block';
        listEl.innerHTML = groups.map((g) => {
            const items = (g.users || []).map((u) => {
                const checked = keep.has(String(u.id)) ? 'checked' : '';
                return userItemHtml(u, checked);
            }).join('');

            return `<details class="role-group" data-loai="${g.loai_order}">
                <summary>
                    <span>${escapeHtml(g.ten_chuc_vu)}</span>
                    <span class="role-meta">${(g.users || []).length} người</span>
                </summary>
                <div class="role-group__body">${items}</div>
            </details>`;
        }).join('');
    }

    if (loadBtn) loadBtn.addEventListener('click', loadStaff);

    // Trang sửa: tự nạp ngay để hiển thị nhân sự đang thuộc ca (đã tick sẵn)
    if (listEl) loadStaff();
});
</script>
@endpush
