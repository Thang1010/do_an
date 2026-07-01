@extends('manager.layout.app')

@section('title', 'Quản lý kho')
@section('breadcrumb', 'Kho & Tài chính / <strong>Quản lý kho</strong>')

@section('content')

@php
    $formatReference = function ($log) {
        if ($log->don_hang_id) {
            return 'Đơn hàng #' . $log->don_hang_id;
        }

        if ($log->chiTieu) {
            return 'Phiếu chi #' . $log->chiTieu->id;
        }

        return '—';
    };
    $currentPurposeValue = $currentPurpose ?? '';
@endphp

@push('styles')
<style>
    .inventory-compact .tab-panel { padding: 12px; }
    .inventory-compact .filter-bar { gap: 8px; }
    .inventory-compact table th,
    .inventory-compact table td { padding: 8px 10px; }
    .inventory-compact .card-header { padding: 10px 14px; }
    .inventory-compact .card-body { padding: 12px 14px; }
    .alert-row.alert-danger-strong {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
</style>
@endpush
<div class="inventory-compact">
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý kho nguyên liệu</h1>
        <p class="page-subtitle">
            Theo dõi tồn kho, lịch sử nhập/xuất kho và xuất báo cáo Excel • {{ $purposeLabel ?? 'Tất cả' }}
        </p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('inventory-batch-import-modal')">Nhập kho</button>
        <button type="button" class="btn btn-danger" onclick="openModal('inventory-batch-export-modal')">Xuất kho</button>
    </div>
</div>


<div id="stock-alert-wrap">
    @include('manager.inventory.partials.stock-alert')
</div>

<div class="tab-card mb-20">
    <div class="tab-list tab-list-inner">
        <button class="tab-btn active" data-tab-key="stock" onclick="activateInventoryTab('stock', this)">Tồn kho hiện tại</button>
        <button class="tab-btn" data-tab-key="import-log" onclick="activateInventoryTab('import-log', this)">Lịch sử nhập kho</button>
        <button class="tab-btn" data-tab-key="export-log" onclick="activateInventoryTab('export-log', this)">Lịch sử xuất kho & điều chỉnh</button>
    </div>

    <div class="tab-panel active p-24" id="tab-stock">
        <form method="GET" action="{{ route('manager.inventory.index') }}" class="filter-bar mb-16">
            <input type="hidden" name="tab" value="stock">
            @if($currentPurposeValue !== '')
                <input type="hidden" name="muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            @endif
            <input type="text" name="search" class="form-control filter-search"
                   value="{{ request('search') }}" placeholder="Tìm nguyên liệu...">
            <select name="trang_thai" class="form-control">
                <option value="">Tất cả trạng thái</option>
                <option value="het" {{ request('trang_thai') === 'het' ? 'selected' : '' }}>Hết hàng</option>
                <option value="sap_het" {{ request('trang_thai') === 'sap_het' ? 'selected' : '' }}>Sắp hết</option>
                <option value="ok" {{ request('trang_thai') === 'ok' ? 'selected' : '' }}>Đủ hàng</option>
            </select>
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <a href="{{ route('manager.inventory.index', array_filter(['tab' => 'stock', 'muc_dich_su_dung' => $currentPurposeValue])) }}" class="btn btn-secondary">Xóa lọc</a>
            <a href="{{ route('manager.inventory.stock.excel', array_filter(array_merge(
                request()->except(['page', 'import_page', 'export_page']),
                ['muc_dich_su_dung' => $currentPurposeValue]
            ))) }}" class="btn btn-success" style="background-color: #27AE60; border-color: #27AE60; color: white; display: flex; align-items: center; gap: 4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Xuất Excel tồn kho
            </a>
        </form>

        <div class="table-wrap" id="low-stock-section">
            <table>
                <thead>
                    <tr>
                        <th>Nguyên liệu</th>
                        <th>Đơn vị</th>
                        <th>Mục đích</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory as $item)
                    @php
                        $maxTieuHao = (float) ($item->max_tieu_hao ?? 0);
                        $divisor = $maxTieuHao > 0 ? $maxTieuHao : 1;
                        $soCup = (int) floor(((float) $item->so_luong) / $divisor);
                        $hetHang = $soCup <= 0;
                        $sapHet = !$hetHang && $soCup <= 3;
                    @endphp
                    <tr>
                        <td><strong>{{ $item->ten_nguyen_lieu }}</strong></td>
                        <td>{{ $item->don_vi_tinh }}</td>
                        <td>{{ $item->muc_dich_su_dung ?: '—' }}</td>
                        <td class="{{ $hetHang ? 'low-stock' : ($sapHet ? 'text-warning' : '') }} font-600">{{ number_format((float) $item->so_luong, 2, ',', '.') }} {{ $item->don_vi_tinh }}</td>
                        <td>
                            @if($hetHang)
                                <span class="badge" style="background-color: #dc3545; color: white;">Hết hàng</span>
                            @elseif($sapHet)
                                <span class="badge" style="background-color: #ffc107; color: #212529;">Sắp hết</span>
                            @else
                                <span class="badge badge-active">Đủ hàng</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-row">
                                <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="openInventoryImportModal(this)"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->ten_nguyen_lieu }}"
                                        data-unit="{{ $item->don_vi_tinh }}"
                                        data-stock="{{ number_format((float) $item->so_luong, 2, '.', '') }}">
                                    Nhập
                                </button>
                                <button type="button" class="btn btn-danger btn-sm"
                                        onclick="openInventoryExportModal(this)"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->ten_nguyen_lieu }}"
                                        data-unit="{{ $item->don_vi_tinh }}"
                                        data-stock="{{ number_format((float) $item->so_luong, 2, '.', '') }}">
                                    Xuất
                                </button>
                                <button type="button" class="btn btn-primary btn-sm"
                                        onclick="openInventoryAdjustModal(this)"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->ten_nguyen_lieu }}"
                                        data-unit="{{ $item->don_vi_tinh }}"
                                        data-stock="{{ number_format((float) $item->so_luong, 2, '.', '') }}">
                                    Điều chỉnh
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            Chưa có nguyên liệu nào trong kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('manager.partials.pager', ['paginator' => $inventory, 'label' => 'nguyên liệu'])

    </div>

    <div class="tab-panel p-24" id="tab-import-log">
        <form method="GET" action="{{ route('manager.inventory.index') }}" class="filter-bar mb-16">
            <input type="hidden" name="tab" value="import-log">
            @if($currentPurposeValue !== '')
                <input type="hidden" name="muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            @endif
            <input type="date" name="import_from_date" value="{{ request('import_from_date') }}" class="form-control">
            <input type="date" name="import_to_date" value="{{ request('import_to_date') }}" class="form-control">
            <input type="text" name="import_manager" value="{{ request('import_manager') }}" class="form-control"
                   list="inventory-manager-list" placeholder="Tên quản lý nhận hàng...">
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <a href="{{ route('manager.inventory.index', array_filter(['tab' => 'import-log', 'muc_dich_su_dung' => $currentPurposeValue])) }}" class="btn btn-secondary">Đặt lại</a>
            <a href="{{ route('manager.inventory.history.import.excel', array_filter([
                'import_from_date' => request('import_from_date'),
                'import_to_date' => request('import_to_date'),
                'import_manager' => request('import_manager'),
                'muc_dich_su_dung' => $currentPurposeValue,
            ])) }}" class="btn btn-success" style="background-color: #27AE60; border-color: #27AE60; color: white; display: flex; align-items: center; gap: 4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Xuất Excel lịch sử nhập
            </a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th><th>Nguyên liệu</th><th>Số lượng nhập</th>
                        <th>Giá nhập</th><th>Tổng tiền</th><th>Tham chiếu</th><th>Người nhập</th><th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($importLog as $log)
                    <tr>
                        <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td><strong>{{ $log->nguyenLieu->ten_nguyen_lieu ?? '—' }}</strong></td>
                        <td>{{ $log->so_luong }} {{ $log->nguyenLieu->don_vi_tinh ?? '' }}</td>
                        <td>
                            @if($log->gia_nhap !== null)
                                {{ number_format($log->gia_nhap, 0, ',', '.') }}đ/{{ $log->nguyenLieu->don_vi_tinh ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <strong>
                                @if($log->gia_nhap !== null)
                                    {{ number_format($log->gia_nhap * $log->so_luong, 0, ',', '.') }}đ
                                @else
                                    —
                                @endif
                            </strong>
                        </td>
                        <td>{{ $formatReference($log) }}</td>
                        <td>{{ $log->nguoiTao->ho_ten ?? '—' }}</td>
                        <td class="text-muted">{{ $log->ghi_chu ?: '—' }}</td>
                        <td>
                            @php
                                $detailPayload = [
                                    'id' => $log->id,
                                    'thoi_gian' => $log->created_at
                                        ? \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s')
                                        : '—',
                                    'nguyen_lieu' => $log->nguyenLieu->ten_nguyen_lieu ?? '—',
                                    'don_vi_tinh' => $log->nguyenLieu->don_vi_tinh ?? '',
                                    'loai_giao_dich' => 'Nhập kho',
                                    'so_luong' => number_format((float) $log->so_luong, 2, ',', '.'),
                                    'don_gia' => $log->gia_nhap !== null
                                        ? number_format($log->gia_nhap, 0, ',', '.') . 'đ'
                                        : '—',
                                    'tong_tien' => $log->gia_nhap !== null
                                        ? number_format($log->gia_nhap * $log->so_luong, 0, ',', '.') . 'đ'
                                        : '—',
                                    'tham_chieu' => $formatReference($log),
                                    'nguoi_thao_tac' => $log->nguoiTao->ho_ten ?? '—',
                                    'ghi_chu' => $log->ghi_chu ?: '—',
                                ];
                            @endphp
                                <button type="button"
                                    class="btn btn-primary btn-sm"
                                    data-detail="{{ json_encode($detailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS) }}"
                                    onclick="showInventoryDetailFromEl(this)">Chi tiết</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="empty-state-sm">
                            Chưa có lịch sử nhập kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('manager.partials.pager', ['paginator' => $importLog, 'label' => 'lượt nhập', 'footerClass' => 'mt-12'])
    </div>

    <div class="tab-panel p-24" id="tab-export-log">
        <form method="GET" action="{{ route('manager.inventory.index') }}" class="filter-bar mb-16">
            <input type="hidden" name="tab" value="export-log">
            @if($currentPurposeValue !== '')
                <input type="hidden" name="muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            @endif
            <input type="date" name="export_from_date" value="{{ request('export_from_date') }}" class="form-control">
            <input type="date" name="export_to_date" value="{{ request('export_to_date') }}" class="form-control">
            <input type="text" name="export_manager" value="{{ request('export_manager') }}" class="form-control"
                   list="inventory-manager-list" placeholder="Tên quản lý xuất hàng...">
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <a href="{{ route('manager.inventory.index', array_filter(['tab' => 'export-log', 'muc_dich_su_dung' => $currentPurposeValue])) }}" class="btn btn-secondary">Đặt lại</a>
            <a href="{{ route('manager.inventory.history.export.excel', array_filter([
                'export_from_date' => request('export_from_date'),
                'export_to_date' => request('export_to_date'),
                'export_manager' => request('export_manager'),
                'muc_dich_su_dung' => $currentPurposeValue,
            ])) }}" class="btn btn-success" style="background-color: #27AE60; border-color: #27AE60; color: white; display: flex; align-items: center; gap: 4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Xuất Excel lịch sử xuất
            </a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th><th>Nguyên liệu</th><th>Loại giao dịch</th>
                        <th>Số lượng biến động</th><th>Tham chiếu</th><th>Lý do / ghi chú</th><th>Người thao tác</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exportLog as $log)
                    <tr>
                        <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td><strong>{{ $log->nguyenLieu->ten_nguyen_lieu ?? '—' }}</strong></td>
                        <td>{{ $log->loai_giao_dich }}</td>
                        <td>{{ number_format((float) $log->so_luong, 2, ',', '.') }} {{ $log->nguyenLieu->don_vi_tinh ?? '' }}</td>
                        <td>{{ $formatReference($log) }}</td>
                        <td>{{ $log->ghi_chu ?: '—' }}</td>
                        <td>{{ $log->nguoiTao->ho_ten ?? '—' }}</td>
                        <td>
                            @php
                                $detailPayload = [
                                    'id' => $log->id,
                                    'thoi_gian' => $log->created_at
                                        ? \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s')
                                        : '—',
                                    'nguyen_lieu' => $log->nguyenLieu->ten_nguyen_lieu ?? '—',
                                    'don_vi_tinh' => $log->nguyenLieu->don_vi_tinh ?? '',
                                    'loai_giao_dich' => $log->loai_giao_dich,
                                    'so_luong' => number_format((float) $log->so_luong, 2, ',', '.'),
                                    'don_gia' => $log->gia_nhap !== null
                                        ? number_format($log->gia_nhap, 0, ',', '.') . 'đ'
                                        : '—',
                                    'tong_tien' => $log->gia_nhap !== null
                                        ? number_format($log->gia_nhap * $log->so_luong, 0, ',', '.') . 'đ'
                                        : '—',
                                    'tham_chieu' => $formatReference($log),
                                    'nguoi_thao_tac' => $log->nguoiTao->ho_ten ?? '—',
                                    'ghi_chu' => $log->ghi_chu ?: '—',
                                ];
                            @endphp
                                <button type="button"
                                    class="btn btn-primary btn-sm"
                                    data-detail="{{ json_encode($detailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS) }}"
                                    onclick="showInventoryDetailFromEl(this)">Chi tiết</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="empty-state-sm">
                            Chưa có lịch sử xuất kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('manager.partials.pager', ['paginator' => $exportLog, 'label' => 'lượt xuất', 'footerClass' => 'mt-12'])
    </div>
</div>

<datalist id="inventory-manager-list">
    @foreach($managerNames as $managerName)
        <option value="{{ $managerName }}"></option>
    @endforeach
</datalist>

{{-- Modal chi tiết lịch sử kho --}}
<div class="modal-backdrop" id="inventory-detail-modal">
    <div class="modal-box" style="max-width: 560px; width: calc(100% - 32px);">
        <div class="modal-header">
            <span class="modal-title">Chi tiết giao dịch kho</span>
            <button class="modal-close" onclick="closeModal('inventory-detail-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-grid-2" style="gap: 14px 20px;">
                <div>
                    <div class="text-12 text-muted">Mã giao dịch</div>
                    <div class="font-600" id="inv-detail-id">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Thời gian</div>
                    <div class="font-600" id="inv-detail-time">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Nguyên liệu</div>
                    <div class="font-600" id="inv-detail-ingredient">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Loại giao dịch</div>
                    <div class="font-600" id="inv-detail-type">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Số lượng</div>
                    <div class="font-600" id="inv-detail-quantity">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Giá nhập</div>
                    <div class="font-600" id="inv-detail-price">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Tổng tiền</div>
                    <div class="font-600 price-text" id="inv-detail-total">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Tham chiếu</div>
                    <div class="font-600" id="inv-detail-ref">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Người thao tác</div>
                    <div class="font-600" id="inv-detail-user">—</div>
                </div>
                <div>
                    <div class="text-12 text-muted">Ghi chú</div>
                    <div class="font-600" id="inv-detail-note">—</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('inventory-detail-modal')">Đóng</button>
        </div>
    </div>
</div>

{{-- Modal nhập kho cho 1 nguyên liệu --}}
<div class="modal-backdrop" id="inventory-import-modal">
    <div class="modal-box" style="max-width: 460px; width: calc(100% - 32px);">
        <form method="POST" action="{{ route('manager.inventory.import.store') }}">
            @csrf
            <input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            <input type="hidden" name="items[0][nguyen_lieu_id]" id="import-modal-id">
            <div class="modal-header">
                <span class="modal-title">Nhập kho nguyên liệu</span>
                <button type="button" class="modal-close" onclick="closeModal('inventory-import-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nguyên liệu</label>
                    <input type="text" class="form-control" id="import-modal-name" readonly
                           style="background-color:#f3f4f6; color:#374151; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Tồn kho hiện tại</label>
                    <input type="text" class="form-control" id="import-modal-stock" readonly
                           style="background-color:#f3f4f6; color:#6b7280; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng nhập <span>*</span></label>
                    <input type="number" step="0.01" min="0.01" name="items[0][so_luong]" id="import-modal-qty"
                           class="form-control" placeholder="Ví dụ: 10" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Đơn giá nhập</label>
                    <input type="number" step="1" min="0" name="items[0][don_gia]" class="form-control"
                           placeholder="VNĐ / đơn vị (tùy chọn)">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" name="items[0][ghi_chu]" class="form-control" maxlength="500"
                           placeholder="Nhập từ nhà cung cấp...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inventory-import-modal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Xác nhận nhập kho</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal xuất kho cho 1 nguyên liệu --}}
<div class="modal-backdrop" id="inventory-export-modal">
    <div class="modal-box" style="max-width: 460px; width: calc(100% - 32px);">
        <form method="POST" action="{{ route('manager.inventory.export.store') }}">
            @csrf
            <input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            <input type="hidden" name="items[0][nguyen_lieu_id]" id="export-modal-id">
            <div class="modal-header">
                <span class="modal-title">Xuất kho nguyên liệu</span>
                <button type="button" class="modal-close" onclick="closeModal('inventory-export-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nguyên liệu</label>
                    <input type="text" class="form-control" id="export-modal-name" readonly
                           style="background-color:#f3f4f6; color:#374151; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Tồn kho hiện tại</label>
                    <input type="text" class="form-control" id="export-modal-stock" readonly
                           style="background-color:#f3f4f6; color:#6b7280; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng xuất <span>*</span></label>
                    <input type="number" step="0.01" min="0.01" name="items[0][so_luong]" id="export-modal-qty"
                           class="form-control" placeholder="Ví dụ: 2.5" required>
                    <p class="form-hint" id="export-modal-hint">Không được vượt quá tồn kho hiện tại.</p>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Lý do xuất kho</label>
                    <input type="text" name="items[0][ly_do]" class="form-control" maxlength="500"
                           placeholder="Xuất pha chế, hao hụt, hủy...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inventory-export-modal')">Hủy</button>
                <button type="submit" class="btn btn-danger">Xác nhận xuất kho</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal điều chỉnh tồn kho (kiểm kê) cho 1 nguyên liệu --}}
<div class="modal-backdrop" id="inventory-adjust-modal">
    <div class="modal-box" style="max-width: 460px; width: calc(100% - 32px);">
        <form method="POST" action="{{ route('manager.inventory.stock.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            <input type="hidden" name="nguyen_lieu_id" id="adjust-modal-id">
            <div class="modal-header">
                <span class="modal-title">Điều chỉnh tồn kho</span>
                <button type="button" class="modal-close" onclick="closeModal('inventory-adjust-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nguyên liệu</label>
                    <input type="text" class="form-control" id="adjust-modal-name" readonly
                           style="background-color:#f3f4f6; color:#374151; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Tồn kho hiện tại (theo hệ thống)</label>
                    <input type="text" class="form-control" id="adjust-modal-stock" readonly
                           style="background-color:#f3f4f6; color:#6b7280; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng thực tế (sau kiểm kê) <span>*</span></label>
                    <input type="number" step="0.01" min="0" name="so_luong_moi" id="adjust-modal-qty"
                           class="form-control" placeholder="Nhập số đếm được thực tế" required>
                    <p class="form-hint">Hệ thống sẽ tự tính chênh lệch và ghi nhận một giao dịch "điều chỉnh".</p>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Lý do điều chỉnh</label>
                    <input type="text" name="ghi_chu" class="form-control" maxlength="500"
                           placeholder="Kiểm kê chênh lệch, hao hụt, nhập sai...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inventory-adjust-modal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Xác nhận điều chỉnh</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal NHẬP KHO nhiều nguyên liệu --}}
<div class="modal-backdrop" id="inventory-batch-import-modal">
    <div class="modal-box" style="max-width: 920px; width: calc(100% - 32px);">
        <form method="POST" action="{{ route('manager.inventory.import.store') }}">
            @csrf
            <input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            <div class="modal-header">
                <span class="modal-title">Nhập kho nhiều nguyên liệu</span>
                <button type="button" class="modal-close" onclick="closeModal('inventory-batch-import-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="batch-import-container" data-next="1">
                    <div class="batch-row" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:12px; flex-wrap:wrap;">
                        <div class="form-group" style="flex:2 1 190px; margin-bottom:0;">
                            <label class="form-label">Nguyên liệu <span>*</span></label>
                            <select name="items[0][nguyen_lieu_id]" class="form-control batch-select" required onchange="syncBatchUnit(this)">
                                <option value="">-- Chọn nguyên liệu --</option>
                                @foreach($nguyenLieus as $nl)
                                    <option value="{{ $nl->id }}" data-unit="{{ $nl->don_vi_tinh }}">{{ $nl->ten_nguyen_lieu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="flex:0 1 80px; margin-bottom:0;">
                            <label class="form-label">Đơn vị</label>
                            <input type="text" class="form-control batch-unit" readonly tabindex="-1" style="background:#f3f4f6; color:#6b7280; cursor:not-allowed;">
                        </div>
                        <div class="form-group" style="flex:1 1 100px; margin-bottom:0;">
                            <label class="form-label">SL nhập <span>*</span></label>
                            <input type="number" step="0.01" min="0.01" name="items[0][so_luong]" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex:1 1 110px; margin-bottom:0;">
                            <label class="form-label">Giá nhập</label>
                            <input type="number" step="1" min="0" name="items[0][don_gia]" class="form-control">
                        </div>
                        <div class="form-group" style="flex:2 1 150px; margin-bottom:0;">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" name="items[0][ghi_chu]" class="form-control" maxlength="500">
                        </div>
                        <div style="margin-bottom:0;">
                            <button type="button" class="btn btn-danger btn-sm batch-remove" onclick="removeBatchRow(this,'batch-import-container')">&times;</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addBatchRow('batch-import-container')">+ Thêm nguyên liệu</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inventory-batch-import-modal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Xác nhận nhập kho</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal XUẤT KHO nhiều nguyên liệu --}}
<div class="modal-backdrop" id="inventory-batch-export-modal">
    <div class="modal-box" style="max-width: 860px; width: calc(100% - 32px);">
        <form method="POST" action="{{ route('manager.inventory.export.store') }}">
            @csrf
            <input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurposeValue }}">
            <div class="modal-header">
                <span class="modal-title">Xuất kho nhiều nguyên liệu</span>
                <button type="button" class="modal-close" onclick="closeModal('inventory-batch-export-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="batch-export-container" data-next="1">
                    <div class="batch-row" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:12px; flex-wrap:wrap;">
                        <div class="form-group" style="flex:2 1 200px; margin-bottom:0;">
                            <label class="form-label">Nguyên liệu <span>*</span></label>
                            <select name="items[0][nguyen_lieu_id]" class="form-control batch-select" required onchange="syncBatchUnit(this)">
                                <option value="">-- Chọn nguyên liệu --</option>
                                @foreach($nguyenLieus as $nl)
                                    <option value="{{ $nl->id }}" data-unit="{{ $nl->don_vi_tinh }}">{{ $nl->ten_nguyen_lieu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="flex:0 1 80px; margin-bottom:0;">
                            <label class="form-label">Đơn vị</label>
                            <input type="text" class="form-control batch-unit" readonly tabindex="-1" style="background:#f3f4f6; color:#6b7280; cursor:not-allowed;">
                        </div>
                        <div class="form-group" style="flex:1 1 110px; margin-bottom:0;">
                            <label class="form-label">SL xuất <span>*</span></label>
                            <input type="number" step="0.01" min="0.01" name="items[0][so_luong]" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex:2 1 170px; margin-bottom:0;">
                            <label class="form-label">Lý do xuất</label>
                            <input type="text" name="items[0][ly_do]" class="form-control" maxlength="500" placeholder="Pha chế, hao hụt, hủy...">
                        </div>
                        <div style="margin-bottom:0;">
                            <button type="button" class="btn btn-danger btn-sm batch-remove" onclick="removeBatchRow(this,'batch-export-container')">&times;</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addBatchRow('batch-export-container')">+ Thêm nguyên liệu</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inventory-batch-export-modal')">Hủy</button>
                <button type="submit" class="btn btn-danger">Xác nhận xuất kho</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // ── Polling cảnh báo tồn kho: tự cập nhật dải hết/sắp hết, không cần F5 ──
    (function () {
        var wrap = document.getElementById('stock-alert-wrap');
        if (!wrap) return;
        var INTERVAL = 20000; // 20 giây
        var inFlight = false;
        var purpose = new URLSearchParams(window.location.search).get('muc_dich_su_dung') || '';
        var POLL_URL = "{{ route('manager.inventory.alert-poll') }}"
            + (purpose ? ('?muc_dich_su_dung=' + encodeURIComponent(purpose)) : '');

        function refresh() {
            if (inFlight || document.hidden) return;
            inFlight = true;
            fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && typeof data.html === 'string') wrap.innerHTML = data.html;
                })
                .catch(function () { /* im lặng */ })
                .finally(function () { inFlight = false; });
        }

        setInterval(refresh, INTERVAL);
    })();

    // ── Nhập/Xuất kho nhiều nguyên liệu (modal) ──
    function syncBatchUnit(sel) {
        const opt = sel.options[sel.selectedIndex];
        const row = sel.closest('.batch-row');
        const unit = row ? row.querySelector('.batch-unit') : null;
        if (unit) unit.value = opt ? (opt.getAttribute('data-unit') || '') : '';
    }

    function addBatchRow(containerId) {
        const container = document.getElementById(containerId);
        const idx = parseInt(container.dataset.next || '1', 10);
        const clone = container.querySelector('.batch-row').cloneNode(true);
        clone.querySelectorAll('[name^="items["]').forEach(el => {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + idx + ']');
            if (el.tagName === 'SELECT') { el.selectedIndex = 0; } else { el.value = ''; }
        });
        const unit = clone.querySelector('.batch-unit');
        if (unit) unit.value = '';
        container.appendChild(clone);
        container.dataset.next = idx + 1;
    }

    function removeBatchRow(btn, containerId) {
        const container = document.getElementById(containerId);
        if (container.querySelectorAll('.batch-row').length > 1) {
            btn.closest('.batch-row').remove();
        } else {
            showNotice('Phải có ít nhất 1 nguyên liệu.');
        }
    }

    function activateInventoryTab(tabKey, btnEl) {
        const tabMap = {
            'stock': 'tab-stock',
            'import-log': 'tab-import-log',
            'export-log': 'tab-export-log'
        };

        const tabId = tabMap[tabKey] || 'tab-stock';
        switchTab(tabId, btnEl);
        syncInventoryTabInputs(tabKey);
    }

    function syncInventoryTabInputs(tabKey) {
        document.querySelectorAll('input[name="tab"]').forEach(input => {
            input.value = tabKey;
        });
    }

    function showInventoryDetail(data) {
        document.getElementById('inv-detail-id').textContent = '#' + data.id;
        document.getElementById('inv-detail-time').textContent = data.thoi_gian;
        document.getElementById('inv-detail-ingredient').textContent = data.nguyen_lieu;
        document.getElementById('inv-detail-type').textContent = data.loai_giao_dich;
        document.getElementById('inv-detail-quantity').textContent = data.so_luong + ' ' + data.don_vi_tinh;
        document.getElementById('inv-detail-price').textContent = data.don_gia;
        document.getElementById('inv-detail-total').textContent = data.tong_tien;
        document.getElementById('inv-detail-ref').textContent = data.tham_chieu;
        document.getElementById('inv-detail-user').textContent = data.nguoi_thao_tac;
        document.getElementById('inv-detail-note').textContent = data.ghi_chu;
        openModal('inventory-detail-modal');
    }

    function formatStockLabel(rawStock, unit) {
        const value = parseFloat(rawStock || '0');
        const formatted = isNaN(value)
            ? '0'
            : value.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return formatted + (unit ? ' ' + unit : '');
    }

    function openInventoryImportModal(button) {
        const d = button.dataset;
        document.getElementById('import-modal-id').value = d.id;
        document.getElementById('import-modal-name').value = d.name;
        document.getElementById('import-modal-stock').value = formatStockLabel(d.stock, d.unit);
        const qty = document.getElementById('import-modal-qty');
        qty.value = '';
        openModal('inventory-import-modal');
        setTimeout(() => qty.focus(), 60);
    }

    function openInventoryExportModal(button) {
        const d = button.dataset;
        document.getElementById('export-modal-id').value = d.id;
        document.getElementById('export-modal-name').value = d.name;
        document.getElementById('export-modal-stock').value = formatStockLabel(d.stock, d.unit);
        const qty = document.getElementById('export-modal-qty');
        qty.value = '';
        qty.max = d.stock;
        document.getElementById('export-modal-hint').textContent =
            'Tối đa ' + formatStockLabel(d.stock, d.unit) + ' (theo tồn kho hiện tại).';
        openModal('inventory-export-modal');
        setTimeout(() => qty.focus(), 60);
    }

    function openInventoryAdjustModal(button) {
        const d = button.dataset;
        document.getElementById('adjust-modal-id').value = d.id;
        document.getElementById('adjust-modal-name').value = d.name;
        document.getElementById('adjust-modal-stock').value = formatStockLabel(d.stock, d.unit);
        const qty = document.getElementById('adjust-modal-qty');
        qty.value = d.stock;
        openModal('inventory-adjust-modal');
        setTimeout(() => { qty.focus(); qty.select(); }, 60);
    }

    function showInventoryDetailFromEl(button) {
        const raw = button?.dataset?.detail;
        if (!raw) {
            return;
        }

        try {
            showInventoryDetail(JSON.parse(raw));
        } catch (error) {
            console.error('Invalid inventory detail payload', error);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const currentTab = new URLSearchParams(window.location.search).get('tab') || 'stock';
        const tabButton = document.querySelector('.tab-btn[data-tab-key="' + currentTab + '"]');

        if (tabButton) {
            activateInventoryTab(currentTab, tabButton);
        }
    });
</script>
@endpush
</div>

@endsection
