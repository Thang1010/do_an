@extends('manager.layout.app')

@php
    $selectedRole = $selectedRole ?? old('vai_tro_ap_dung', $position->vai_tro_ap_dung ?? 'nhân viên');
@endphp

@section('title', 'Sửa chức vụ')
@section('breadcrumb', 'Nhân sự / <a href="' . route('manager.positions.index') . '">Quản lý chức vụ</a> / <strong>Sửa</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa chức vụ {{ $position->ten_chuc_vu }}</h1>
        <p class="page-subtitle">Cập nhật thông tin chức vụ</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

<div class="card" style="max-width: 900px;">
    <div class="card-header">
        <span class="card-title">Thông tin chức vụ</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('manager.positions.update', ['id' => $position->id]) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Tên chức vụ <span>*</span></label>
                <input type="text" name="ten_chuc_vu" class="form-control" maxlength="100" value="{{ old('ten_chuc_vu', $position->ten_chuc_vu) }}" required>
                @error('ten_chuc_vu')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Vai trò áp dụng <span>*</span></label>
                <select name="vai_tro_ap_dung" class="form-control" required>
                    @foreach(($allowedRoles ?? ['nhân viên' => 'Nhân viên', 'quản lý' => 'Quản lý']) as $value => $label)
                        <option value="{{ $value }}" {{ $selectedRole === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('vai_tro_ap_dung')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea name="mo_ta_chuc_vu" class="form-control" rows="4" maxlength="1000">{{ old('mo_ta_chuc_vu', $position->mo_ta_chuc_vu) }}</textarea>
                @error('mo_ta_chuc_vu')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Loại hình làm việc</label>
                <select name="loai_hinh_lam_viec" class="form-control">
                    <option value="">-- Chọn loại hình --</option>
                    @php
                        // Nếu trước đó lưu dạng array (cũ) thì lấy phần tử đầu, nếu không thì lấy nguyên
                        $currentLoaiHinh = $position->loai_hinh_lam_viec;
                        if (is_array($currentLoaiHinh)) {
                            $currentLoaiHinh = $currentLoaiHinh[0] ?? '';
                        }
                        $selectedLoaiHinh = old('loai_hinh_lam_viec', $currentLoaiHinh);
                    @endphp
                    <option value="toàn thời gian" {{ $selectedLoaiHinh === 'toàn thời gian' ? 'selected' : '' }}>Toàn thời gian</option>
                    <option value="bán thời gian" {{ $selectedLoaiHinh === 'bán thời gian' ? 'selected' : '' }}>Bán thời gian</option>
                </select>
                @error('loai_hinh_lam_viec')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; gap: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Lương cơ bản (VND)</label>
                    <input type="text" name="luong_co_ban" class="form-control format-money" value="{{ old('luong_co_ban', $position->luong_co_ban) }}">
                    @error('luong_co_ban')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Lương theo giờ (VND)</label>
                    <input type="text" name="luong_theo_gio" class="form-control format-money" value="{{ old('luong_theo_gio', $position->luong_theo_gio) }}">
                    @error('luong_theo_gio')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 10px;">
                <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formatMoney = (val) => {
        let number = val.replace(/[^\d]/g, '');
        if (!number) return '';
        return Number(number).toLocaleString('en-US');
    };

    document.querySelectorAll('.format-money').forEach(input => {
        input.addEventListener('input', function(e) {
            let cursorPosition = this.selectionStart;
            let originalLength = this.value.length;
            this.value = formatMoney(this.value);
            let newLength = this.value.length;
            this.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
        });
        
        if (this.value) {
            this.value = formatMoney(this.value);
        }
    });

    const form = document.querySelector('form');
    if(form) {
        form.addEventListener('submit', function() {
            document.querySelectorAll('.format-money').forEach(input => {
                input.value = input.value.replace(/,/g, '');
            });
        });
    }
});
</script>
@endpush
