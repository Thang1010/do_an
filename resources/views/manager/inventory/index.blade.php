@extends('manager.layout.app')

@section('title', 'Quản lý kho')
@section('breadcrumb', 'Kho & Tài chính / <strong>Quản lý kho</strong>')

@section('content')

@php
    $formatReference = function ($log) {
        if (! $log->tham_chieu_loai || ! $log->tham_chieu_id) {
            return '—';
        }

        return match ($log->tham_chieu_loai) {
            'don_hang' => 'Đơn hàng #' . $log->tham_chieu_id,
            'phieu_nhap' => 'Phiếu nhập #' . $log->tham_chieu_id,
            default => strtoupper($log->tham_chieu_loai) . ' #' . $log->tham_chieu_id,
        };
    };
    $currentPurposeValue = $currentPurpose ?? '';
    $isSupplyPurpose = $currentPurposeValue === 'Vật tư';
@endphp

@push('styles')
<style>
    .inventory-compact .tab-panel { padding: 12px; }
    .inventory-compact .filter-bar { gap: 8px; }
    .inventory-compact table th,
    .inventory-compact table td { padding: 8px 10px; }
    .inventory-compact .card-header { padding: 10px 14px; }
    .inventory-compact .card-body { padding: 12px 14px; }
</style>
@endpush
<div class="inventory-compact">
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý kho nguyên liệu</h1>
        <p class="page-subtitle">
            Theo dõi tồn kho, lịch sử nhập/xuất kho và xuất báo cáo Excel • {{ $purposeLabel ?? 'Tất cả' }}
            @if($isSupplyPurpose)
                • Có thể chỉnh tay số lượng tồn
            @endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.inventory.import', array_filter(['muc_dich_su_dung' => $currentPurpose])) }}" class="btn btn-primary">Nhập kho</a>
        <a href="{{ route('manager.inventory.export', array_filter(['muc_dich_su_dung' => $currentPurpose])) }}" class="btn btn-danger">Xuất kho</a>
    </div>
</div>

@if(!empty($purposeTabs))
<div class="tab-container">
    <div class="tab-list">
        @foreach($purposeTabs as $value => $info)
            <a href="{{ route('manager.inventory.index', array_merge(request()->except('muc_dich_su_dung', 'page'), $value !== '' ? ['muc_dich_su_dung' => $value] : [])) }}"
               class="tab-btn {{ (string) $currentPurposeValue === (string) $value ? 'active' : '' }}">
                {{ $info['label'] }}
                @if(($info['count'] ?? 0) > 0)
                    <span class="tab-count {{ (string) $currentPurposeValue === (string) $value ? 'tab-count-active' : 'tab-count-default' }}">
                        {{ $info['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
@endif

@if($lowCount > 0)
<div class="alert alert-warning alert-row">
    <span>Có <strong>{{ $lowCount }}</strong> nguyên liệu đã hết — cần nhập kho ngay!</span>
    <button onclick="document.getElementById('low-stock-section').scrollIntoView({behavior:'smooth'})"
            class="btn btn-warning btn-sm">Xem ngay</button>
</div>
@endif

<div class="tab-card mb-20">
    <div class="tab-list tab-list-inner">
        <button class="tab-btn active" data-tab-key="stock" onclick="activateInventoryTab('stock', this)">Tồn kho hiện tại</button>
        <button class="tab-btn" data-tab-key="import-log" onclick="activateInventoryTab('import-log', this)">Lịch sử nhập kho</button>
        <button class="tab-btn" data-tab-key="export-log" onclick="activateInventoryTab('export-log', this)">Lịch sử xuất/điều chỉnh</button>
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
                <option value="low" {{ request('trang_thai') === 'low' ? 'selected' : '' }}>Hết hàng</option>
                <option value="ok" {{ request('trang_thai') === 'ok' ? 'selected' : '' }}>Đủ hàng</option>
            </select>
            <button type="submit" class="btn btn-secondary">Lọc</button>
            <a href="{{ route('manager.inventory.index', array_filter(['tab' => 'stock', 'muc_dich_su_dung' => $currentPurposeValue])) }}" class="btn btn-secondary">Đặt lại</a>
            <a href="{{ route('manager.inventory.stock.excel', array_filter(array_merge(
                request()->except(['page', 'import_page', 'export_page']),
                ['muc_dich_su_dung' => $currentPurposeValue]
            ))) }}"
               class="btn btn-primary">Xuất Excel tồn kho</a>
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
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory as $item)
                    <tr>
                        <td><strong>{{ $item->ten_nguyen_lieu }}</strong></td>
                        <td>{{ $item->don_vi_tinh }}</td>
                        <td>{{ $item->muc_dich_su_dung ?: '—' }}</td>
                        <td class="{{ $item->so_luong <= 0 ? 'low-stock' : '' }} font-600">{{ number_format((float) $item->so_luong, 2, ',', '.') }} {{ $item->don_vi_tinh }}</td>
                        <td>
                            @if($item->so_luong <= 0)
                                <span class="badge badge-inactive">Hết hàng</span>
                            @else
                                <span class="badge badge-active">Đủ hàng</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-row">
                                <a href="{{ route('manager.inventory.import', array_filter(['nguyen_lieu_id' => $item->id, 'muc_dich_su_dung' => $currentPurposeValue])) }}" class="btn btn-secondary btn-sm">
                                    Nhập thêm
                                </a>
                                <a href="{{ route('manager.inventory.export', array_filter(['nguyen_lieu_id' => $item->id, 'muc_dich_su_dung' => $currentPurposeValue])) }}" class="btn btn-danger btn-sm">
                                    Xuất bớt
                                </a>
                                @if($isSupplyPurpose)
                                    @if($isSupplyPurpose)
                                        <form method="POST" action="{{ route('manager.inventory.stock.update') }}" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="nguyen_lieu_id" value="{{ $item->id }}">
                                            <input type="hidden" name="return_muc_dich_su_dung" value="{{ $currentPurposeValue }}">
                                            <input type="number"
                                                   name="so_luong_moi"
                                                   step="0.01"
                                                   min="0"
                                                   class="form-control"
                                                   style="width: 120px;"
                                                   value="{{ number_format((float) $item->so_luong, 2, '.', '') }}">
                                            <button type="submit" class="btn btn-primary btn-sm">Cập nhật tồn</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            Chưa có nguyên liệu nào trong kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($inventory->hasPages())
        <div class="card-footer">
            <div class="pagination-footer">
                <span class="text-sm text-muted">Hiển thị {{ $inventory->firstItem() }}–{{ $inventory->lastItem() }} / {{ $inventory->total() }}</span>
                {{ $inventory->appends(request()->query())->links() }}
            </div>
        </div>
        @endif

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
            ])) }}" class="btn btn-primary">Xuất Excel lịch sử nhập</a>
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

        @if($importLog->hasPages())
        <div class="card-footer mt-12">
            <div class="pagination-footer">
                <span class="text-sm text-muted">Hiển thị {{ $importLog->firstItem() }}–{{ $importLog->lastItem() }} / {{ $importLog->total() }}</span>
                {{ $importLog->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
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
            ])) }}" class="btn btn-primary">Xuất Excel lịch sử xuất</a>
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

        @if($exportLog->hasPages())
        <div class="card-footer mt-12">
            <div class="pagination-footer">
                <span class="text-sm text-muted">Hiển thị {{ $exportLog->firstItem() }}–{{ $exportLog->lastItem() }} / {{ $exportLog->total() }}</span>
                {{ $exportLog->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
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

@push('scripts')
<script>
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
