@extends('manager.layout.app')

@section('title', $isStoreOwner ? 'Thêm nguyên liệu' : 'Gửi yêu cầu thêm nguyên liệu')
@section('breadcrumb', 'Kho & Tài chính / <a href="' . route('manager.ingredients.index') . '">Quản lý nguyên liệu</a> / <strong>Thêm nguyên liệu</strong>')

@section('content')
@php
    $oldIngredients = old('ingredients');
    if (!is_array($oldIngredients) || count($oldIngredients) === 0) {
        $oldIngredients = [
            ['ten_nguyen_lieu' => '', 'don_vi_tinh' => ''],
        ];
    }
    $hasPurposeOptions = !empty($purposeOptions);
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $isStoreOwner ? 'Thêm nguyên liệu mới' : 'Gửi yêu cầu thêm nguyên liệu' }}</h1>
        <p class="page-subtitle">
            {{ $isStoreOwner ? 'Bạn có thể thêm nhiều nguyên liệu cùng lúc và lưu trực tiếp.' : 'Bạn có thể thêm nhiều nguyên liệu cùng lúc để gửi chủ cửa hàng xác nhận một lần.' }}
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.ingredients.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-body">
        @if(!$isStoreOwner)
            <div class="alert alert-info mb-16">
                Yêu cầu của bạn sẽ được gửi lên chủ cửa hàng. Nguyên liệu chỉ xuất hiện chính thức sau khi được duyệt.
            </div>
        @endif

        <form method="POST" action="{{ route('manager.ingredients.store') }}" id="ingredient-create-form">
            @csrf

            <div class="table-wrap">
                <table id="ingredient-create-table">
                    <thead>
                        <tr>
                            <th class="col-stt">#</th>
                            <th>Tên nguyên liệu</th>
                            <th>Đơn vị tính</th>
                            <th>Mục đích sử dụng</th>
                            <th class="col-action">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($oldIngredients as $index => $row)
                        @php
                            $purposeValue = $row['muc_dich_su_dung'] ?? '';
                            $customPurpose = '';
                            $purposeSelect = $purposeValue;
                            if ($purposeValue !== '' && !in_array($purposeValue, $purposeOptions ?? [], true)) {
                                $customPurpose = $purposeValue;
                                $purposeSelect = '__other__';
                            }
                            if (!$hasPurposeOptions) {
                                if ($purposeValue === '') {
                                    $purposeSelect = '__other__';
                                } else {
                                    $customPurpose = $purposeValue;
                                    $purposeSelect = '__other__';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="ingredient-row-no">{{ $index + 1 }}</td>
                            <td>
                                <input type="text" name="ingredients[{{ $index }}][ten_nguyen_lieu]" class="form-control" value="{{ $row['ten_nguyen_lieu'] ?? '' }}" maxlength="150" placeholder="Ví dụ: Sữa tươi không đường">
                            </td>
                            <td>
                                <select name="ingredients[{{ $index }}][don_vi_tinh]" class="form-control">
                                    <option value="">Chọn đơn vị</option>
                                    @foreach($unitOptions as $unit)
                                        <option value="{{ $unit }}" {{ ($row['don_vi_tinh'] ?? '') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="ingredients[{{ $index }}][muc_dich_su_dung]" class="form-control js-purpose-select">
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
                                       name="ingredients[{{ $index }}][muc_dich_su_dung_khac]"
                                       class="form-control js-purpose-input"
                                       value="{{ $customPurpose }}"
                                       placeholder="Nhập mục đích khác"
                                        style="margin-top: 8px; display: none;">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredientRow(this)">Xóa dòng</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="addIngredientRow()">+ Thêm dòng mới</button>

                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $isStoreOwner ? 'Lưu nguyên liệu' : 'Gửi yêu cầu xác nhận' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

<script id="ingredient-unit-options" type="application/json">@json($unitOptions ?? [])</script>
<script id="ingredient-purpose-options" type="application/json">@json($purposeOptions ?? [])</script>

@push('scripts')
<script>
    const unitOptions = JSON.parse(
        document.getElementById('ingredient-unit-options')?.textContent || '[]'
    );
    const purposeOptions = JSON.parse(
        document.getElementById('ingredient-purpose-options')?.textContent || '[]'
    );

    function buildSelectOptions(options) {
        return options.map((option) => `<option value="${option}">${option}</option>`).join('');
    }

    function togglePurposeInput(select) {
        const row = select.closest('tr');
        const input = row ? row.querySelector('.js-purpose-input') : null;
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

    function reindexIngredientRows() {
        const rows = document.querySelectorAll('#ingredient-create-table tbody tr');

        rows.forEach((row, index) => {
            row.querySelector('.ingredient-row-no').textContent = String(index + 1);

            row.querySelectorAll('input, select').forEach((field) => {
                if (!field.name || !field.name.startsWith('ingredients[')) {
                    return;
                }

                field.name = field.name.replace(/ingredients\[\d+\]/, 'ingredients[' + index + ']');
            });
        });
    }

    function addIngredientRow() {
        const tbody = document.querySelector('#ingredient-create-table tbody');
        const index = tbody.querySelectorAll('tr').length;
        const row = document.createElement('tr');

        const unitSelect = '<select name="ingredients[' + index + '][don_vi_tinh]" class="form-control">' +
            '<option value="">Chọn đơn vị</option>' +
            buildSelectOptions(unitOptions) +
            '</select>';

        const hasPurposeOptions = Array.isArray(purposeOptions) && purposeOptions.length > 0;
        const purposeSelect = '<select name="ingredients[' + index + '][muc_dich_su_dung]" class="form-control js-purpose-select">' +
            (hasPurposeOptions
                ? '<option value="">Chọn mục đích</option>' + buildSelectOptions(purposeOptions) + '<option value="__other__">Khác...</option>'
                : '<option value="__other__" selected>Khác...</option>') +
            '</select>' +
            '<input type="text" name="ingredients[' + index + '][muc_dich_su_dung_khac]" class="form-control js-purpose-input" placeholder="Nhập mục đích khác" style="margin-top: 8px; display: none;">';

        row.innerHTML = '' +
            '<td class="ingredient-row-no">' + (index + 1) + '</td>' +
            '<td><input type="text" name="ingredients[' + index + '][ten_nguyen_lieu]" class="form-control" maxlength="150" placeholder="Ví dụ: Trà đen"></td>' +
            '<td>' + unitSelect + '</td>' +
            '<td>' + purposeSelect + '</td>' +
            '<td><button type="button" class="btn btn-danger btn-sm" onclick="removeIngredientRow(this)">Xóa dòng</button></td>';

        tbody.appendChild(row);
        reindexIngredientRows();

        const select = row.querySelector('.js-purpose-select');
        if (select) {
            togglePurposeInput(select);
        }
    }

    function removeIngredientRow(button) {
        const tbody = document.querySelector('#ingredient-create-table tbody');
        const rows = tbody.querySelectorAll('tr');

        if (rows.length <= 1) {
            alert('Cần giữ lại ít nhất một dòng nguyên liệu.');
            return;
        }

        button.closest('tr').remove();
        reindexIngredientRows();
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.matches('.js-purpose-select')) {
            togglePurposeInput(event.target);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-purpose-select').forEach((select) => {
            togglePurposeInput(select);
        });
    });
</script>
@endpush
