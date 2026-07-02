@extends('manager.layout.app')

@section('title', 'Sửa nguyên liệu')
@section('breadcrumb', 'Kho & Tài chính / <a href="' . route('manager.ingredients.index') . '">Quản lý nguyên liệu</a> / <strong>Sửa nguyên liệu</strong>')

@section('content')
@php
    $purposeValue = old('muc_dich_su_dung', $ingredient->muc_dich_su_dung ?? '');
    $customPurpose = '';
    $purposeSelect = $purposeValue;
    if ($purposeValue !== '' && !in_array($purposeValue, $purposeOptions ?? [], true)) {
        $customPurpose = $purposeValue;
        $purposeSelect = '__other__';
    }
    $hasPurposeOptions = !empty($purposeOptions);
    if (!$hasPurposeOptions) {
        if ($purposeValue === '') {
            $purposeSelect = '__other__';
        } else {
            $customPurpose = $purposeValue;
            $purposeSelect = '__other__';
        }
    }
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Sửa nguyên liệu</h1>
        <p class="page-subtitle">Cập nhật thông tin nguyên liệu</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.ingredients.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="card" style="max-width: 980px;">
    <div class="card-body">
        <form method="POST" action="{{ route('manager.ingredients.update', ['id' => $ingredient->id]) }}">
            @csrf
            @method('PUT')

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Tên nguyên liệu <span>*</span></label>
                    <input type="text" name="ten_nguyen_lieu" class="form-control" maxlength="150" required value="{{ old('ten_nguyen_lieu', $ingredient->ten_nguyen_lieu) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Đơn vị tính <span>*</span></label>
                    <select name="don_vi_tinh" class="form-control" required>
                        @foreach($unitOptions as $unit)
                            <option value="{{ $unit }}" {{ old('don_vi_tinh', $ingredient->don_vi_tinh) === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Mục đích sử dụng <span>*</span></label>
                <select name="muc_dich_su_dung" class="form-control js-purpose-select" required>
                    @if($hasPurposeOptions)
                        <option value="">Chọn mục đích</option>
                        @foreach($purposeOptions ?? [] as $purpose)
                            <option value="{{ $purpose }}" {{ $purposeSelect === $purpose ? 'selected' : '' }}>{{ $purpose }}</option>
                        @endforeach
                        <option value="__other__" {{ $purposeSelect === '__other__' ? 'selected' : '' }}>Khác...</option>
                    @else
                        <option value="__other__" selected>Khác...</option>
                    @endif
                </select>
                <input type="text"
                       name="muc_dich_su_dung_khac"
                       class="form-control js-purpose-input"
                       value="{{ $customPurpose }}"
                       placeholder="Nhập mục đích khác"
                      style="margin-top: 8px; display: none;">
                @error('muc_dich_su_dung')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="action-row mt-12">
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePurposeInput(select) {
        const input = select.closest('form')?.querySelector('.js-purpose-input');
        if (!input) {
            return;
        }

        if (select.value === '__other__') {
            input.style.display = 'block';
            input.focus();
        } else {
            input.style.display = 'none';
            input.value = '';
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.matches('.js-purpose-select')) {
            togglePurposeInput(event.target);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const select = document.querySelector('.js-purpose-select');
        if (select) {
            togglePurposeInput(select);
        }
    });
</script>
@endpush
