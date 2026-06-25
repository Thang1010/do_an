@extends('manager.layout.app')

@php
    $profileRoleLabel = $profileRoleLabel ?? (((string) ($position->vai_tro_ap_dung ?? 'nhân viên') === 'quản lý') ? 'Quản lý' : 'Nhân viên');
    $isManagerPosition = $isManagerPosition ?? ((string) ($position->vai_tro_ap_dung ?? 'nhân viên') === 'quản lý');
@endphp

@section('title', 'Chi tiết chức vụ')
@section('breadcrumb')
    Nhân sự / <a href="{{ route('manager.positions.index') }}">Quản lý chức vụ</a> / <strong>Chi tiết</strong>
@endsection

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $position->ten_chuc_vu }}</h1>
            <p class="page-subtitle">Thông tin chức vụ và danh sách {{ strtolower($profileRoleLabel) }} thuộc chức vụ này
            </p>
        </div>
        <div class="page-actions">
            @if($isStoreOwnerActor ?? false)
                <a href="{{ route('manager.positions.edit', ['id' => $position->id]) }}" class="btn btn-primary">Sửa chức vụ</a>
            @endif
            <a href="{{ route('manager.positions.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
        </div>
    </div>

    <div class="card mb-20">
        <div class="card-header">
            <span class="card-title">Thông tin chức vụ</span>
        </div>
        <div class="table-wrap">
            <table>
                <tbody>
                    <tr>
                        <th style="width: 200px; background: #fafaf9;">Tên chức vụ</th>
                        <td><strong>{{ $position->ten_chuc_vu }}</strong></td>
                    </tr>
                    <tr>
                        <th style="background: #fafaf9;">Vai trò áp dụng</th>
                        <td>{{ $position->vai_tro_ap_dung ?? 'nhân viên' }}</td>
                    </tr>
                    <tr>
                        <th style="background: #fafaf9;">Lương cơ bản</th>
                        <td>{{ $position->luong_co_ban ? number_format($position->luong_co_ban, 0, ',', '.') . 'đ' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <th style="background: #fafaf9;">Lương theo giờ</th>
                        <td>{{ $position->luong_theo_gio ? number_format($position->luong_theo_gio, 0, ',', '.') . 'đ/h' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <th style="background: #fafaf9;">Loại hình làm việc</th>
                        <td>{{ $position->loai_hinh_lam_viec ? ucfirst(trim($position->loai_hinh_lam_viec, '"')) : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <th style="background: #fafaf9;">Mô tả chức vụ</th>
                        <td>{{ $position->mo_ta_chuc_vu ?: 'Chưa có mô tả.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span class="card-title">{{ $profileRoleLabel }} thuộc chức vụ này</span>
            @if($isStoreOwnerActor ?? false)
                <button type="button" class="btn btn-primary btn-sm"
                    onclick="document.getElementById('assign-profiles-modal').style.display='flex'; document.body.style.overflow='hidden';">Thêm
                    nhân sự áp dụng</button>
            @endif
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th>Họ tên</th>
                        <th>Vai trò tài khoản</th>
                        <th>Trạng thái</th>
                        <th>Chức vụ</th>
                        <th class="col-action-xl text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $index => $profile)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $profile->nguoiDung?->hoSoNhanVien?->ho_ten ?? $profile->nguoiDung?->email ?? '—' }}</td>
                            <td>{{ $profile->nguoiDung?->vai_tro ?? '—' }}</td>
                            <td>{{ $profile->nguoiDung?->trang_thai ?? '—' }}</td>
                            <td>{{ $profile->chucVu?->ten_chuc_vu ?? '—' }}</td>
                            <td>
                                <div class="action-row" style="justify-content: center;">
                                    <a href="{{ route('manager.users.show', $profile->nguoi_dung_id) }}"
                                        class="btn btn-secondary btn-sm">Chi tiết</a>
                                    @if($isStoreOwnerActor ?? false)
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showRemoveProfileModal({{ $profile->id }}, '{{ $profile->nguoiDung?->hoSoNhanVien?->ho_ten ?? $profile->nguoiDung?->email ?? 'Nhân sự' }}')">Xóa</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Chưa có {{ strtolower($profileRoleLabel) }} nào thuộc chức vụ
                                này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- Assign Profiles Modal -->
    @if($isStoreOwnerActor ?? false)
        <style>
            #assign-profiles-modal .force-password-modal__panel {
                color: #fff;
            }

            #assign-profiles-modal .card-header {
                background: transparent;
                color: #fff;
                border-bottom: 1px solid #33261a !important;
            }

            #assign-profiles-modal .card-title {
                color: #c49a6c;
                font-size: 1.25rem;
            }

            #assign-profiles-modal .card-footer {
                background: transparent;
                border-top: 1px solid #33261a !important;
            }

            #assign-profiles-modal table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }

            #assign-profiles-modal thead tr {
                background: transparent;
            }

            #assign-profiles-modal th {
                background: transparent;
                color: #c49a6c;
                font-weight: 600;
                padding: 12px 16px;
                text-align: left;
                border-bottom: 1px solid #c49a6c;
            }

            #assign-profiles-modal td {
                padding: 12px 16px;
                color: #e7e7e4ff;
                border-bottom: 1px solid #33261a;
                vertical-align: middle;
                transition: background-color 0.2s;
            }

            #assign-profiles-modal tbody tr:hover {
                background-color: rgba(196, 154, 108, 0.08) !important;
            }
            #assign-profiles-modal tbody tr:hover td {
                background-color: transparent;
            }

            #assign-profiles-modal .profile-checkbox {
                width: 18px;
                height: 18px;
                accent-color: #c49a6c;
                cursor: pointer;
            }

            #assign-profiles-modal small {
                color: #a1a1aa !important;
                font-size: 0.85rem;
                display: block;
                margin-top: 4px;
            }

            #assign-profiles-modal .badge-current {
                background: #3f3f46;
                color: #e4e4e7;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 0.85rem;
            }
        </style>
        <div id="assign-profiles-modal" class="force-password-modal" style="display:none;" role="dialog" aria-modal="true">
            <div class="force-password-modal__backdrop"
                onclick="this.parentElement.style.display='none'; document.body.style.overflow='';"></div>
            <div class="force-password-modal__panel" style="width: min(650px, 92vw); padding: 0;">
                <div class="card-header" style="padding: 20px;">
                    <span class="card-title">Thêm {{ strtolower($profileRoleLabel) }} vào chức vụ</span>
                </div>
                <form method="POST" action="{{ route('manager.positions.assign', $position->id) }}">
                    @csrf
                    <div class="card-body" style="padding: 0; max-height: 55vh; overflow-y: auto;">
                        @if($unassignedProfiles->isEmpty())
                            <div style="padding: 40px 20px; text-align: center; color: #a1a1aa;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; opacity: 0.5;">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <line x1="19" y1="8" x2="19" y2="14"></line>
                                    <line x1="22" y1="11" x2="16" y2="11"></line>
                                </svg>
                                <p>Không có {{ strtolower($profileRoleLabel) }} nào khả dụng để gán.</p>
                            </div>
                        @else
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">
                                            <input type="checkbox" id="check-all-profiles" class="profile-checkbox"
                                                onclick="document.querySelectorAll('.profile-checkbox').forEach(cb => cb.checked = this.checked)">
                                        </th>
                                        <th>Thông tin nhân sự</th>
                                        <th>Chức vụ hiện tại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unassignedProfiles as $up)
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="profile_ids[]" value="{{ $up->id }}"
                                                    class="profile-checkbox">
                                            </td>
                                            <td>
                                                <strong
                                                    style="color: #fff; font-size: 0.95rem;">{{ $up->ho_ten ?? $up->nguoiDung?->hoSoNhanVien?->ho_ten ?? 'Chưa cập nhật tên' }}</strong>
                                                <small>{{ $up->nguoiDung?->email }}</small>
                                            </td>
                                            <td>
                                                @if($up->chuc_vu_id)
                                                    <span class="badge-current">{{ $up->chucVu?->ten_chuc_vu }}</span>
                                                @else
                                                    <span class="badge-current"
                                                        style="background: transparent; border: 1px dashed #52525b;">Chưa có</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    <div class="card-footer" style="padding: 16px 20px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-secondary"
                            onclick="document.getElementById('assign-profiles-modal').style.display='none'; document.body.style.overflow='';"
                            style="background: transparent; border: 1px solid #52525b; color: #e4e4e7;">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary" {{ $unassignedProfiles->isEmpty() ? 'disabled' : '' }}
                            style="background: #c49a6c; color: #1a120c; border: none; font-weight: 600;">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Remove Profile Modal -->
        <div id="remove-profile-modal" class="force-password-modal" style="display:none;" role="dialog" aria-modal="true">
            <div class="force-password-modal__backdrop"
                onclick="this.parentElement.style.display='none'; document.body.style.overflow='';"></div>
            <div class="force-password-modal__panel" style="width: min(420px, 92vw);">
                <div class="force-password-modal__title"
                    style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Xác nhận xóa
                </div>
                <p class="force-password-modal__desc"
                    style="margin-bottom: 25px; margin-top: 10px; padding: 0 10px; text-align: center;">
                    Bạn có chắc chắn muốn xóa nhân sự <strong id="remove-profile-name"></strong> khỏi chức vụ này không?
                </p>
                <form id="remove-profile-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div style="display: flex; justify-content: center; gap: 10px;">
                        <button type="button" class="btn btn-secondary"
                            onclick="document.getElementById('remove-profile-modal').style.display='none'; document.body.style.overflow='';">Hủy</button>
                        <button type="submit" class="btn btn-danger">Xóa nhân sự</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function showRemoveProfileModal(profileId, profileName) {
                document.getElementById('remove-profile-name').innerText = profileName;
                document.getElementById('remove-profile-form').action = '{{ route("manager.positions.index") }}/{{ $position->id }}/remove-profile/' + profileId;
                document.getElementById('remove-profile-modal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        </script>
    @endif

@endsection