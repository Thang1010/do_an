@extends('manager.layout.app')

@section('title', 'Hồ sơ cá nhân')
@section('breadcrumb', 'Tổng quan / <strong>Hồ sơ cá nhân</strong>')

@section('content')
    @php
        $managerProfile = $user->hoSoQuanLy;
        $storeProfile = $user->cuaHang;
        $staffProfile = $user->hoSoNhanVien;
        $customerProfile = $user->hoSoKhachHang;
        $isStoreOwner = $user->vai_tro === 'chủ cửa hàng';
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Hồ sơ cá nhân</h1>
            <p class="page-subtitle">Cập nhật thông tin tài khoản và thông tin theo vai trò hiện tại</p>
        </div>
    </div>

    <form method="POST" action="{{ route('manager.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card mb-20">
            <div class="card-header">
                <span class="card-title">Ảnh đại diện</span>
            </div>
            <div class="card-body">
                @if(!$isStoreOwner)
                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                    <img id="avatar-preview" src="{{ $user->avatar_url }}" alt="Ảnh đại diện"
                        style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #E2D9C8; cursor:pointer; flex-shrink:0;"
                        onclick="document.getElementById('mgr-avatar-input').click()">
                    <div>
                        <input type="file" name="avatar" id="mgr-avatar-input" accept="image/*" style="display:none;">
                        <button type="button" class="btn btn-secondary btn-sm"
                            onclick="document.getElementById('mgr-avatar-input').click()">Chọn ảnh</button>
                        <p style="font-size:.8rem; color:#888; margin-top:6px;">JPG, PNG, GIF, WEBP — tối đa 2MB. Bấm "Lưu hồ sơ" ở dưới cùng để cập nhật.</p>
                    </div>
                </div>
                @else
                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                    <img src="{{ $user->avatar_url }}" alt="Ảnh đại diện"
                        style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #E2D9C8; flex-shrink:0;">
                    <div>
                        <p style="font-size:.9rem; color:#666;">Chủ cửa hàng không thể thay đổi ảnh đại diện.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="card mb-20">
            <div class="card-header">
                <span class="card-title">Thông tin tài khoản</span>
            </div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Họ tên @if(!$isStoreOwner)<span>*</span>@endif</label>
                        <input type="text" name="ho_ten" class="form-control" value="{{ old('ho_ten', $user->ho_ten) }}"
                            {{ $isStoreOwner ? '' : 'required' }}>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vai trò</label>
                        <input type="text" class="form-control" value="{{ ucfirst($user->vai_tro) }}" disabled style="background-color: #f3f4f6; cursor: not-allowed; color: #6b7280;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email @if($isStoreOwner)<span>*</span>@endif</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" {{ $isStoreOwner ? 'required' : '' }}>
                    </div>
                </div>
            </div>
        </div>

        @if($user->vai_tro === 'quản lý' || $isStoreOwner)
            <div class="card mb-20">
                @if(!$isStoreOwner)
                <div class="card-header">
                    <span class="card-title">Thông tin quản lý</span>
                </div>
                @endif
                <div class="card-body">
                    <div class="form-grid-2">
                        @if($user->vai_tro === 'quản lý')
                            <div class="form-group">
                                <label class="form-label">Chức vụ quản lý</label>
                                <select name="chuc_vu_id" class="form-control" disabled style="background-color: #f3f4f6; cursor: not-allowed; color: #6b7280; appearance: none; -webkit-appearance: none; -moz-appearance: none;">
                                    <option value="">-- Chọn chức vụ --</option>
                                    @foreach($positions ?? [] as $position)
                                        <option value="{{ $position->id }}" {{ (string) old('chuc_vu_id', $managerProfile?->chuc_vu_id) === (string) $position->id ? 'selected' : '' }}>
                                            {{ $position->ten_chuc_vu }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Ngày vào làm</label>
                                <input type="date" name="ngay_vao_lam" class="form-control"
                                    value="{{ old('ngay_vao_lam', optional($managerProfile?->ngay_vao_lam)->format('Y-m-d')) }}" disabled style="background-color: #f3f4f6; cursor: not-allowed; color: #6b7280;">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="ngay_sinh" class="form-control"
                                    value="{{ old('ngay_sinh', optional($managerProfile?->ngay_sinh)->format('Y-m-d')) }}">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Số điện thoại</label>
                                <input type="text" name="so_dien_thoai" class="form-control"
                                    value="{{ old('so_dien_thoai', $managerProfile?->so_dien_thoai) }}">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Địa chỉ</label>
                                <textarea name="dia_chi_tam_chu" class="form-control" rows="2">{{ old('dia_chi_tam_chu', $managerProfile?->dia_chi_tam_chu) }}</textarea>
                            </div>
                        @endif

                        @if($isStoreOwner)
                            <div class="form-group">
                                <label class="form-label">Số điện thoại cửa hàng</label>
                                <input type="text" name="cua_hang_so_dien_thoai" class="form-control"
                                    value="{{ old('cua_hang_so_dien_thoai', $storeProfile?->so_dien_thoai) }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Địa chỉ cửa hàng</label>
                                <input type="text" name="cua_hang_dia_chi" class="form-control @error('cua_hang_dia_chi') is-invalid @enderror"
                                    value="{{ old('cua_hang_dia_chi', $storeProfile?->dia_chi) }}">
                                @error('cua_hang_dia_chi')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Giờ mở cửa</label>
                                <input type="time" name="cua_hang_gio_mo_cua" class="form-control"
                                    value="{{ old('cua_hang_gio_mo_cua', $storeProfile?->gio_mo_cua ? \Illuminate\Support\Str::substr($storeProfile->gio_mo_cua, 0, 5) : '') }}">
                                @error('cua_hang_gio_mo_cua')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Giờ đóng cửa</label>
                                <input type="time" name="cua_hang_gio_dong_cua" class="form-control"
                                    value="{{ old('cua_hang_gio_dong_cua', $storeProfile?->gio_dong_cua ? \Illuminate\Support\Str::substr($storeProfile->gio_dong_cua, 0, 5) : '') }}">
                                @error('cua_hang_gio_dong_cua')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Liên kết trang (Fanpage)</label>
                                <input type="url" name="cua_hang_lien_ket_trang" class="form-control"
                                    value="{{ old('cua_hang_lien_ket_trang', $storeProfile?->lien_ket_trang) }}"
                                    placeholder="https://facebook.com/xmcoffee">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Mô tả cửa hàng</label>
                                <textarea name="cua_hang_mo_ta" class="form-control"
                                    rows="3">{{ old('cua_hang_mo_ta', $storeProfile?->mo_ta) }}</textarea>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($user->vai_tro === 'nhân viên')
            <div class="card mb-20">
                <div class="card-header">
                    <span class="card-title">Thông tin nhân viên</span>
                </div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Mã nhân viên</label>
                            <input type="text" name="ma_nhan_vien" class="form-control"
                                value="{{ old('ma_nhan_vien', $staffProfile?->ma_nhan_vien) }}"
                                placeholder="Để trống để tự sinh mã">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Chức vụ</label>
                            <select name="chuc_vu_id" class="form-control">
                                <option value="">-- Chọn chức vụ --</option>
                                @foreach($positions ?? [] as $position)
                                    <option value="{{ $position->id }}" {{ (string) old('chuc_vu_id', $staffProfile?->chuc_vu_id) === (string) $position->id ? 'selected' : '' }}>
                                        {{ $position->ten_chuc_vu }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lương cơ bản</label>
                            <input type="text" name="luong_co_ban" class="form-control format-money"
                                value="{{ old('luong_co_ban', $staffProfile?->luong_co_ban) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngày vào làm</label>
                            <input type="date" name="ngay_vao_lam" class="form-control"
                                value="{{ old('ngay_vao_lam', optional($staffProfile?->ngay_vao_lam)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card mb-20">
                <div class="card-header">
                    <span class="card-title">Thông tin khách hàng</span>
                </div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Giới tính</label>
                            <select name="gioi_tinh" class="form-control">
                                <option value="">Chọn giới tính</option>
                                <option value="nam" {{ old('gioi_tinh', $customerProfile?->gioi_tinh) === 'nam' ? 'selected' : '' }}>Nam</option>
                                <option value="nữ" {{ old('gioi_tinh', $customerProfile?->gioi_tinh) === 'nữ' ? 'selected' : '' }}>Nữ</option>
                                <option value="khác" {{ old('gioi_tinh', $customerProfile?->gioi_tinh) === 'khác' ? 'selected' : '' }}>Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" name="ngay_sinh" class="form-control"
                                value="{{ old('ngay_sinh', optional($customerProfile?->ngay_sinh)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="dia_chi" class="form-control"
                                rows="3">{{ old('dia_chi', $customerProfile?->dia_chi) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body" style="display: flex; justify-content: flex-end; gap: 10px;">
                <a href="{{ route('manager.dashboard') }}" class="btn btn-secondary">Quay lại</a>
                <button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.getElementById('mgr-avatar-input')?.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => document.getElementById('avatar-preview').src = e.target.result;
                reader.readAsDataURL(file);
            });
        </script>
    @endpush
@endsection