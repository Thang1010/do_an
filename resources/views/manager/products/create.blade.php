@extends('manager.layout.app')

@section('title', isset($product) ? 'Sửa sản phẩm' : 'Thêm sản phẩm')
@section('breadcrumb', 'Sản phẩm / <strong>' . (isset($product) ? 'Chỉnh sửa' : 'Thêm mới') . '</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ isset($product) ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' }}</h1>
        <p class="page-subtitle">{{ isset($product) ? $product->ten_san_pham : 'Điền đầy đủ thông tin sản phẩm' }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.products.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<form method="POST"
      action="{{ isset($product) ? route('manager.products.update', $product->id) : route('manager.products.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if(isset($product)) @method('PUT') @endif

    <div class="layout-form-page">

        {{-- Left: Main Info --}}
        <div class="flex-col-18">
            {{-- Basic Info --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Thông tin cơ bản</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Tên sản phẩm <span>*</span></label>
                        <input type="text" name="ten_san_pham" class="form-control @error('ten_san_pham') is-invalid @enderror"
                               placeholder="Ví dụ: Cà phê nâu đá"
                               value="{{ old('ten_san_pham', $product->ten_san_pham ?? '') }}" required>
                        @error('ten_san_pham')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mô tả ngắn</label>
                        <input type="text" name="mo_ta_ngan" class="form-control"
                               placeholder="Mô tả ngắn gọn hiển thị trên card sản phẩm"
                               value="{{ old('mo_ta_ngan', $product->mo_ta_ngan ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mô tả chi tiết</label>
                        <textarea name="mo_ta" class="form-control" rows="4"
                                  placeholder="Mô tả đầy đủ về sản phẩm...">{{ old('mo_ta', $product->mo_ta ?? '') }}</textarea>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group form-group-flat">
                            <label class="form-label">Danh mục <span>*</span></label>
                            <select name="danh_muc_id" class="form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($danhMucs ?? [] as $dm)
                                    <option value="{{ $dm->id }}"
                                        {{ old('danh_muc_id', $product->danh_muc_id ?? '') == $dm->id ? 'selected' : '' }}>
                                        {{ $dm->ten_danh_muc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-group-flat">
                            <label class="form-label">Trạng thái</label>
                            <select name="trang_thai_ban" class="form-control">
                                <option value="dang_ban" {{ old('trang_thai_ban', $product->trang_thai_ban ?? 'dang_ban') === 'dang_ban' ? 'selected' : '' }}>
                                    Đang bán
                                </option>
                                <option value="ngung_ban" {{ old('trang_thai_ban', $product->trang_thai_ban ?? '') === 'ngung_ban' ? 'selected' : '' }}>
                                    Ngừng bán
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Giá bán</span></div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group form-group-flat">
                            <label class="form-label">Giá gốc (đ) <span>*</span></label>
                            <input type="number" name="gia_goc" class="form-control @error('gia_goc') is-invalid @enderror"
                                   placeholder="35000"
                                   value="{{ old('gia_goc', $product->gia_goc ?? '') }}" min="0" required>
                            @error('gia_goc')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group form-group-flat">
                            <label class="form-label">Giá khuyến mãi (đ)</label>
                            <input type="number" name="gia_khuyen_mai" class="form-control"
                                   placeholder="Để trống nếu không KM"
                                   value="{{ old('gia_khuyen_mai', $product->gia_khuyen_mai ?? '') }}" min="0">
                            <div class="form-hint">Giá này sẽ hiển thị thay thế giá gốc</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sizes --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Kích cỡ (Size)</span>
                </div>
                <div class="card-body">
                    <div id="sizes-container">
                        @php
                            $sizes = old('sizes');
                            if ($sizes === null) {
                                if (isset($product) && $product->sanPhamKichCo->isNotEmpty()) {
                                    $basePrice = (float) ($product->gia_goc ?? 0);
                                    $sizes = $product->sanPhamKichCo->map(function ($spkc) use ($basePrice) {
                                        $giaBan = (float) ($spkc->gia_ban ?? 0);
                                        return [
                                            'kich_co_id' => $spkc->kich_co_id,
                                            'he_so_gia' => $basePrice > 0 ? round($giaBan / $basePrice, 2) : 1,
                                            'ma_kich_co_moi' => '',
                                            'ten_kich_co_moi' => '',
                                            'mo_ta_kich_co_moi' => '',
                                        ];
                                    })->values()->all();
                                } else {
                                    $sizes = [[
                                        'kich_co_id' => '',
                                        'he_so_gia' => 1,
                                        'ma_kich_co_moi' => '',
                                        'ten_kich_co_moi' => '',
                                        'mo_ta_kich_co_moi' => '',
                                    ]];
                                }
                            }
                        @endphp
                        @foreach($sizes as $i => $size)
                        @php $isOther = ($size['kich_co_id'] ?? '') === 'khac'; @endphp
                        <div class="size-row">
                            <div class="form-grid-2">
                                <div class="form-group form-group-flat">
                                <label class="form-label">Tên size</label>
                                    <select name="sizes[{{ $i }}][kich_co_id]" class="form-control size-select" onchange="toggleOtherFields(this)">
                                        <option value="">-- Chọn size --</option>
                                        @foreach(($kichCos ?? []) as $kc)
                                            <option value="{{ $kc->id }}" {{ (string)($size['kich_co_id'] ?? '') === (string)$kc->id ? 'selected' : '' }}>
                                                {{ $kc->ten_kich_co }} ({{ $kc->ma_kich_co ?? 'không mã' }})
                                            </option>
                                        @endforeach
                                        <option value="khac" {{ $isOther ? 'selected' : '' }}>Khác</option>
                                    </select>
                                    @error("sizes.$i.kich_co_id")<div class="form-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group size-field-flex">
                                    <div class="flex-1">
                                        <label class="form-label">Hệ số giá</label>
                                        <input type="number" name="sizes[{{ $i }}][he_so_gia]" class="form-control"
                                               step="0.1" min="1" placeholder="1.0" value="{{ $size['he_so_gia'] ?? 1 }}">
                                        @error("sizes.$i.he_so_gia")<div class="form-error">{{ $message }}</div>@enderror
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.size-row').remove()">Xóa</button>
                                </div>
                            </div>

                            <div class="other-size-fields {{ $isOther ? 'visible' : '' }}" style="display:{{ $isOther ? 'grid' : 'none' }};">
                                <div class="form-group form-group-flat">
                                    <label class="form-label">Mã kích cỡ mới</label>
                                    <input type="text" name="sizes[{{ $i }}][ma_kich_co_moi]" class="form-control"
                                           value="{{ $size['ma_kich_co_moi'] ?? '' }}" placeholder="VD: XL">
                                    @error("sizes.$i.ma_kich_co_moi")<div class="form-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group form-group-flat">
                                    <label class="form-label">Tên kích cỡ mới</label>
                                    <input type="text" name="sizes[{{ $i }}][ten_kich_co_moi]" class="form-control"
                                           value="{{ $size['ten_kich_co_moi'] ?? '' }}" placeholder="VD: Extra Large">
                                    @error("sizes.$i.ten_kich_co_moi")<div class="form-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group form-group-span">
                                    <label class="form-label">Mô tả size mới</label>
                                    <input type="text" name="sizes[{{ $i }}][mo_ta_kich_co_moi]" class="form-control"
                                           value="{{ $size['mo_ta_kich_co_moi'] ?? '' }}" placeholder="Mô tả thêm cho kích cỡ mới">
                                    @error("sizes.$i.mo_ta_kich_co_moi")<div class="form-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addSizeRow()">+ Thêm size</button>
                    <div class="form-hint mt-8">Hệ số giá = 1 nghĩa là giá gốc. 1.5 = 150% giá gốc.</div>
                </div>
            </div>

            {{-- Recipes --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Công thức nguyên liệu</span>
                </div>
                <div class="card-body">
                    @php
                        $recipes = old('recipes');
                        if ($recipes === null) {
                            if (isset($product) && $product->congThucSanPham->isNotEmpty()) {
                                $recipes = $product->congThucSanPham->map(function ($recipe) {
                                    return [
                                        'nguyen_lieu_id' => $recipe->nguyen_lieu_id,
                                        'so_luong_can' => $recipe->so_luong_can,
                                    ];
                                })->values()->all();
                            } else {
                                $recipes = [[
                                    'nguyen_lieu_id' => '',
                                    'so_luong_can' => '',
                                ]];
                            }
                        }
                    @endphp

                    <div class="table-wrap">
                        <table id="recipe-table">
                            <thead>
                                <tr>
                                    <th>Nguyên liệu</th>
                                    <th>Đơn vị</th>
                                    <th>Số lượng tiêu hao</th>
                                    <th class="col-action">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recipes as $rIndex => $recipe)
                                <tr>
                                    <td>
                                        <select name="recipes[{{ $rIndex }}][nguyen_lieu_id]" class="form-control recipe-ingredient" onchange="syncRecipeUnit(this)">
                                            <option value="">-- Chọn nguyên liệu --</option>
                                            @foreach(($nguyenLieus ?? []) as $nguyenLieu)
                                                <option
                                                    value="{{ $nguyenLieu->id }}"
                                                    data-unit="{{ $nguyenLieu->don_vi_tinh }}"
                                                    {{ (string)($recipe['nguyen_lieu_id'] ?? '') === (string)$nguyenLieu->id ? 'selected' : '' }}
                                                >
                                                    {{ $nguyenLieu->ten_nguyen_lieu }} ({{ $nguyenLieu->don_vi_tinh }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("recipes.$rIndex.nguyen_lieu_id")<div class="form-error">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <span class="recipe-unit">{{ $recipe['nguyen_lieu_id'] ? optional(($nguyenLieus ?? collect())->firstWhere('id', (int) $recipe['nguyen_lieu_id']))->don_vi_tinh : '' }}</span>
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" min="0" name="recipes[{{ $rIndex }}][so_luong_can]" class="form-control" value="{{ $recipe['so_luong_can'] ?? '' }}" placeholder="0.00">
                                        @error("recipes.$rIndex.so_luong_can")<div class="form-error">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRecipeRow(this)">Xóa</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addRecipeRow()">+ Thêm nguyên liệu</button>
                    <div class="form-hint mt-8">Công thức này sẽ dùng để trừ kho khi đơn hàng được xác nhận.</div>
                </div>
            </div>
        </div>

        {{-- Right: Image & Options --}}
        <div class="flex-col-18">
            {{-- Upload ảnh đại diện --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Ảnh đại diện</span></div>
                <div class="card-body">
                    <div class="img-upload-area" id="upload-area" onclick="document.getElementById('img-input').click()">
                        <img id="img-preview"
                             src="{{ old('anh_chinh') ? '' : (isset($product) && $product->hinh_anh_chinh ? asset('storage/' . $product->hinh_anh_chinh) : '') }}"
                             class="img-preview-upload {{ isset($product) && $product->hinh_anh_chinh ? '' : 'hidden' }}">
                        <p>Nhấn để tải ảnh lên</p>
                        <p class="img-upload-hint">JPG, PNG, WEBP — tối đa 2MB</p>
                    </div>
                    <input type="file" id="img-input" name="anh_chinh" accept="image/*"
                           class="file-input-hidden" onchange="previewImage(this)">
                </div>
            </div>

            {{-- Notes --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Ghi chú nội bộ</span></div>
                <div class="card-body">
                    <textarea name="ghi_chu" class="form-control" rows="3"
                              placeholder="Ghi chú cho nhân viên pha chế...">{{ old('ghi_chu', $product->ghi_chu ?? '') }}</textarea>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex-col-10">
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ isset($product) ? 'Lưu thay đổi' : 'Thêm sản phẩm' }}
                </button>
                <a href="{{ route('manager.products.index') }}" class="btn btn-secondary btn-lg text-center">Hủy</a>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
let sizeIndex = {{ isset($sizes) ? count($sizes) : 1 }};
let recipeIndex = {{ isset($recipes) ? count($recipes) : 1 }};

function addSizeRow() {
    const container = document.getElementById('sizes-container');
    const options = `{!! collect($kichCos ?? [])->map(function($kc){
        $label = e($kc->ten_kich_co . ' (' . ($kc->ma_kich_co ?? 'không mã') . ')');
        return '<option value="' . $kc->id . '">' . $label . '</option>';
    })->implode('') !!}`;
    const row = document.createElement('div');
    row.className = 'size-row';
    row.innerHTML = `
        <div class="form-grid-2">
            <div class="form-group form-group-flat">
                <label class="form-label">Tên size</label>
                <select name="sizes[${sizeIndex}][kich_co_id]" class="form-control size-select" onchange="toggleOtherFields(this)">
                    <option value="">-- Chọn size --</option>
                    ${options}
                    <option value="khac">Khác</option>
                </select>
            </div>
            <div class="form-group size-field-flex">
                <div class="flex-1">
                    <label class="form-label">Hệ số giá</label>
                    <input type="number" name="sizes[${sizeIndex}][he_so_gia]" class="form-control" step="0.1" min="1" placeholder="1.0" value="1">
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.size-row').remove()">Xóa</button>
            </div>
        </div>
        <div class="other-size-fields" style="display:none;">
            <div class="form-group form-group-flat">
                <label class="form-label">Mã kích cỡ mới</label>
                <input type="text" name="sizes[${sizeIndex}][ma_kich_co_moi]" class="form-control" placeholder="VD: XL">
            </div>
            <div class="form-group form-group-flat">
                <label class="form-label">Tên kích cỡ mới</label>
                <input type="text" name="sizes[${sizeIndex}][ten_kich_co_moi]" class="form-control" placeholder="VD: Extra Large">
            </div>
            <div class="form-group form-group-span">
                <label class="form-label">Mô tả size mới</label>
                <input type="text" name="sizes[${sizeIndex}][mo_ta_kich_co_moi]" class="form-control" placeholder="Mô tả thêm cho kích cỡ mới">
            </div>
        </div>`;
    container.appendChild(row);
    sizeIndex++;
}

function toggleOtherFields(selectEl) {
    const row = selectEl.closest('.size-row');
    const wrapper = row.querySelector('.other-size-fields');
    const isOther = selectEl.value === 'khac';
    if (isOther) {
        wrapper.classList.add('visible');
        wrapper.style.display = 'grid';
    } else {
        wrapper.classList.remove('visible');
        wrapper.style.display = 'none';
    }
}

document.querySelectorAll('.size-select').forEach(toggleOtherFields);

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('img-preview');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function syncRecipeUnit(selectEl) {
    const row = selectEl.closest('tr');
    const unitCell = row ? row.querySelector('.recipe-unit') : null;
    const selected = selectEl.options[selectEl.selectedIndex];

    if (unitCell) {
        unitCell.textContent = selected ? (selected.getAttribute('data-unit') || '') : '';
    }
}

function addRecipeRow() {
    const tbody = document.querySelector('#recipe-table tbody');
    if (!tbody) {
        return;
    }

    const options = `{!! collect($nguyenLieus ?? [])->map(function($nl){
        $label = e($nl->ten_nguyen_lieu . ' (' . $nl->don_vi_tinh . ')');
        return '<option value="' . $nl->id . '" data-unit="' . $nl->don_vi_tinh . '">' . $label . '</option>';
    })->implode('') !!}`;

    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select name="recipes[${recipeIndex}][nguyen_lieu_id]" class="form-control recipe-ingredient" onchange="syncRecipeUnit(this)">
                <option value="">-- Chọn nguyên liệu --</option>
                ${options}
            </select>
        </td>
        <td><span class="recipe-unit"></span></td>
        <td>
            <input type="number" step="0.001" min="0" name="recipes[${recipeIndex}][so_luong_can]" class="form-control" placeholder="0.00">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRecipeRow(this)">Xóa</button>
        </td>
    `;

    tbody.appendChild(row);
    recipeIndex++;
}

function removeRecipeRow(button) {
    const tbody = document.querySelector('#recipe-table tbody');
    if (!tbody) {
        return;
    }

    const rows = tbody.querySelectorAll('tr');
    if (rows.length <= 1) {
        alert('Cần giữ lại ít nhất một nguyên liệu trong công thức.');
        return;
    }

    button.closest('tr').remove();
}

document.querySelectorAll('.recipe-ingredient').forEach(syncRecipeUnit);
</script>
@endpush
