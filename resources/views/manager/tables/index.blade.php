@extends('layouts.manager')

@section('title', 'Quản lý bàn ăn')
@section('breadcrumb', 'Kinh doanh / <strong>Quản lý bàn ăn</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý bàn ăn</h1>
        <p class="page-subtitle">Quản lý số bàn phục vụ cho gọi món và đặt bàn</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('create-table-modal')">Thêm bàn ăn</button>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('manager.tables.index') }}" class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm số bàn..." value="{{ request('search') }}">
        <select name="trang_thai" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="trong" {{ request('trang_thai') === 'trong' ? 'selected' : '' }}>Trống</option>
            <option value="dang_phuc_vu" {{ request('trang_thai') === 'dang_phuc_vu' ? 'selected' : '' }}>Đang phục vụ</option>
            <option value="da_dat" {{ request('trang_thai') === 'da_dat' ? 'selected' : '' }}>Đã đặt</option>
            <option value="ngung_su_dung" {{ request('trang_thai') === 'ngung_su_dung' ? 'selected' : '' }}>Ngưng sử dụng</option>
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.tables.index') }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Số bàn</th>
                    <th>Trạng thái</th>
                    <th>Trạng thái thanh toán</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tables ?? [] as $i => $table)
                @php
                    $stt = method_exists($tables, 'firstItem') && $tables->firstItem()
                        ? ($tables->firstItem() + $i)
                        : ($i + 1);

                    $statusClass = match ($table->trang_thai) {
                        'đang phục vụ' => 'badge-brew',
                        'đã đặt' => 'badge-pending',
                        'ngưng sử dụng' => 'badge-inactive',
                        default => 'badge-active',
                    };

                    $paymentClass = 'badge-default';
                    $paymentLabel = 'Không có';
                    $showPaymentBadge = false;

                    if (in_array($table->trang_thai, ['đang phục vụ', 'đã đặt'], true)) {
                        $showPaymentBadge = true;

                        if (($table->so_don_chua_thanh_toan ?? 0) > 0) {
                            $paymentClass = 'badge-pending';
                            $paymentLabel = 'Chưa thanh toán';
                        } elseif (($table->so_don_da_thanh_toan ?? 0) > 0) {
                            $paymentClass = 'badge-done';
                            $paymentLabel = 'Đã thanh toán';
                        } else {
                            $paymentClass = 'badge-pending';
                            $paymentLabel = 'Chưa thanh toán';
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $stt }}</td>
                    <td><span class="font-600">{{ $table->so_ban }}</span></td>
                    <td>
                        <span class="badge {{ $statusClass }}">{{ ucfirst($table->trang_thai) }}</span>
                    </td>
                    <td>
                        @if($showPaymentBadge)
                            <span class="badge {{ $paymentClass }}">{{ $paymentLabel }}</span>
                        @else
                            <span class="text-muted">{{ $paymentLabel }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.tables.show', $table->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                            <button class="btn btn-secondary btn-sm" onclick="openModal('edit-table-modal-{{ $table->id }}')">Sửa</button>
                            <form method="POST" action="{{ route('manager.tables.destroy', $table->id) }}"
                                  onsubmit="return confirmDelete(this, 'Xóa bàn {{ $table->so_ban }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        Chưa có bàn ăn nào. <button class="btn btn-link link-primary" onclick="openModal('create-table-modal')">Thêm ngay</button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($tables) && method_exists($tables, 'hasPages') && $tables->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $tables->firstItem() }}-{{ $tables->lastItem() }} / {{ $tables->total() }} bàn ăn
            </span>
            {{ $tables->links() }}
        </div>
    </div>
    @endif
</div>

<div class="modal-backdrop" id="create-table-modal">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Thêm bàn ăn mới</span>
            <button class="modal-close" onclick="closeModal('create-table-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="create-table-form" method="POST" action="{{ route('manager.tables.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Số bàn <span>*</span></label>
                    <input type="text" name="so_ban" class="form-control" maxlength="20" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-control">
                        <option value="trong">Trống</option>
                        <option value="dang_phuc_vu">Đang phục vụ</option>
                        <option value="da_dat">Đã đặt</option>
                        <option value="ngung_su_dung">Ngưng sử dụng</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-table-modal')">Hủy</button>
            <button class="btn btn-primary" onclick="document.getElementById('create-table-form').submit()">Lưu bàn ăn</button>
        </div>
    </div>
</div>

@foreach($tables ?? [] as $table)
<div class="modal-backdrop" id="edit-table-modal-{{ $table->id }}">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Sửa bàn ăn</span>
            <button class="modal-close" onclick="closeModal('edit-table-modal-{{ $table->id }}')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="edit-table-form-{{ $table->id }}" method="POST" action="{{ route('manager.tables.update', $table->id) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Số bàn <span>*</span></label>
                    <input type="text" name="so_ban" class="form-control" maxlength="20" value="{{ $table->so_ban }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-control">
                        <option value="trong" {{ $table->trang_thai === 'trống' ? 'selected' : '' }}>Trống</option>
                        <option value="dang_phuc_vu" {{ $table->trang_thai === 'đang phục vụ' ? 'selected' : '' }}>Đang phục vụ</option>
                        <option value="da_dat" {{ $table->trang_thai === 'đã đặt' ? 'selected' : '' }}>Đã đặt</option>
                        <option value="ngung_su_dung" {{ $table->trang_thai === 'ngưng sử dụng' ? 'selected' : '' }}>Ngưng sử dụng</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('edit-table-modal-{{ $table->id }}')">Hủy</button>
            <button class="btn btn-primary" onclick="document.getElementById('edit-table-form-{{ $table->id }}').submit()">Cập nhật</button>
        </div>
    </div>
</div>
@endforeach

@endsection
