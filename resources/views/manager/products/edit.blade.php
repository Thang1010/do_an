@extends('layouts.manager')

@section('title', 'Sửa sản phẩm')
@section('breadcrumb', 'Sản phẩm / <strong>Chỉnh sửa</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Chỉnh sửa sản phẩm</h1>
        <p class="page-subtitle">{{ $product->ten_san_pham }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.products.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<form method="POST"
      action="{{ route('manager.products.update', $product->id) }}"
      enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="layout-form-page">
        <div class="flex-col-18">
            <div class="card">
                <div class="card-header"><span class="card-title">Thông tin cơ bản</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Tên sản phẩm <span>*</span></label>
                        <input type="text"
                               name="ten_san_pham"
                               class="form-control @error('ten_san_pham') is-invalid @enderror"
                               value="{{ old('ten_san_pham', $product->ten_san_pham) }}"
                               required>
                        @error('ten_san_pham')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mô tả chi tiết</label>
                        <textarea name="mo_ta"
                                  class="form-control @error('mo_ta') is-invalid @enderror"
                                  rows="4">{{ old('mo_ta', $product->mo_ta) }}</textarea>
                        @error('mo_ta')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group form-group-flat">
                            <label class="form-label">Danh mục <span>*</span></label>
                            <select name="danh_muc_id"
                                    class="form-control @error('danh_muc_id') is-invalid @enderror"
                                    required>
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($danhMucs as $dm)
                                    <option value="{{ $dm->id }}"
                                        {{ (string) old('danh_muc_id', $product->danh_muc_id) === (string) $dm->id ? 'selected' : '' }}>
                                        {{ $dm->ten_danh_muc }}
                                    </option>
                                @endforeach
                            </select>
                            @error('danh_muc_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group form-group-flat">
                            <label class="form-label">Trạng thái</label>
                            <select name="trang_thai_ban"
                                    class="form-control @error('trang_thai_ban') is-invalid @enderror">
                                <option value="dang_ban" {{ old('trang_thai_ban', $product->trang_thai_ban) === 'dang_ban' ? 'selected' : '' }}>
                                    Đang bán
                                </option>
                                <option value="ngung_ban" {{ old('trang_thai_ban', $product->trang_thai_ban) === 'ngung_ban' ? 'selected' : '' }}>
                                    Ngừng bán
                                </option>
                            </select>
                            @error('trang_thai_ban')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">Giá bán</span></div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group form-group-flat">
                            <label class="form-label">Giá gốc (đ) <span>*</span></label>
                            <input type="number"
                                   name="gia_goc"
                                   class="form-control @error('gia_goc') is-invalid @enderror"
                                   value="{{ old('gia_goc', $product->gia_goc) }}"
                                   min="0"
                                   required>
                            @error('gia_goc')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group form-group-flat">
                            <label class="form-label">Giá khuyến mãi (đ)</label>
                            <input type="number"
                                   name="gia_khuyen_mai"
                                   class="form-control @error('gia_khuyen_mai') is-invalid @enderror"
                                   value="{{ old('gia_khuyen_mai', $product->gia_khuyen_mai) }}"
                                   min="0">
                            @error('gia_khuyen_mai')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-col-18">
            <div class="card">
                <div class="card-header"><span class="card-title">Ảnh đại diện</span></div>
                <div class="card-body">
                    <div class="img-upload-area" onclick="document.getElementById('img-input').click()">
                        <img id="img-preview"
                             src="{{ old('anh_chinh') ? '' : ($product->hinh_anh_chinh ? asset('storage/' . $product->hinh_anh_chinh) : '') }}"
                             class="img-preview-upload {{ $product->hinh_anh_chinh ? '' : 'hidden' }}">
                        <p>Nhấn để sửa hình ảnh</p>
                        <p class="img-upload-hint">Sửa ảnh sản phẩm</p>
                    </div>
                    <input type="file"
                           id="img-input"
                           name="anh_chinh"
                           accept="image/*"
                           class="file-input-hidden"
                           onchange="previewImage(this)">
                    @error('anh_chinh')<div class="form-error mt-8">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="flex-col-10">
                <button type="submit" class="btn btn-primary btn-lg">Lưu thay đổi</button>
                <a href="{{ route('manager.products.index') }}" class="btn btn-secondary btn-lg text-center">Hủy</a>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
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
</script>
@endpush
