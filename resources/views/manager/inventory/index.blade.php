@extends('layouts.manager')

@section('title', 'Quản lý kho')
@section('breadcrumb', 'Kho & Tài chính / <strong>Quản lý kho</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý kho nguyên liệu</h1>
        <p class="page-subtitle">Theo dõi tồn kho và cảnh báo nguyên liệu sắp hết</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.inventory.import') }}" class="btn btn-primary">Nhập kho</a>
        <a href="{{ route('manager.inventory.export') }}" class="btn btn-secondary">Xuất kho</a>
    </div>
</div>

{{-- Alert: low stock --}}
@if($lowCount > 0)
<div class="alert alert-warning alert-row">
    <span>Có <strong>{{ $lowCount }}</strong> nguyên liệu sắp hết — cần nhập kho ngay!</span>
    <button onclick="document.getElementById('low-stock-section').scrollIntoView({behavior:'smooth'})"
            class="btn btn-warning btn-sm">Xem ngay</button>
</div>
@endif

{{-- Tabs --}}
<div class="tab-card mb-20">
    <div class="tab-list tab-list-inner">
        <button class="tab-btn active" onclick="switchTab('tab-stock', this)">Tồn kho hiện tại</button>
        <button class="tab-btn" onclick="switchTab('tab-import-log', this)">Lịch sử nhập kho</button>
        <button class="tab-btn" onclick="switchTab('tab-export-log', this)">Lịch sử xuất kho</button>
    </div>

    {{-- Tồn kho --}}
    <div class="tab-panel active p-24" id="tab-stock">
        <div class="filter-bar mb-16">
            <input type="text" class="form-control filter-search" placeholder="Tìm nguyên liệu...">
            <select class="form-control">
                <option value="">Tất cả trạng thái</option>
                <option value="low">Sắp hết</option>
                <option value="ok">Đủ hàng</option>
            </select>
        </div>
        <div class="table-wrap" id="low-stock-section">
            <table>
                <thead>
                    <tr>
                        <th>Nguyên liệu</th>
                        <th>Đơn vị</th>
                        <th>Tồn kho</th>
                        <th>Mức tối thiểu</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory as $item)
                    <tr>
                        <td><strong>{{ $item->ten_nguyen_lieu }}</strong></td>
                        <td>{{ $item->don_vi_tinh }}</td>
                        <td class="{{ $item->so_luong_ton <= $item->muc_canh_bao ? 'low-stock' : '' }} font-600">
                            {{ $item->so_luong_ton }} {{ $item->don_vi_tinh }}
                        </td>
                        <td class="text-muted">{{ $item->muc_canh_bao }} {{ $item->don_vi_tinh }}</td>
                        <td>
                            @if($item->so_luong_ton == 0)
                                <span class="badge badge-inactive">Hết hàng</span>
                            @elseif($item->so_luong_ton <= $item->muc_canh_bao)
                                <span class="badge badge-pending">Sắp hết</span>
                            @else
                                <span class="badge badge-active">Đủ hàng</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-row">
                                <a href="{{ route('manager.inventory.import') }}" class="btn btn-secondary btn-sm">Sửa</a>
                                <a href="{{ route('manager.inventory.export') }}" class="btn btn-danger btn-sm">Xóa</a>
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
        @if($inventory->hasPages())
        <div class="card-footer">
            <div class="pagination-footer">
                <span class="text-sm text-muted">Hiển thị {{ $inventory->firstItem() }}–{{ $inventory->lastItem() }} / {{ $inventory->total() }}</span>
                {{ $inventory->appends(request()->query())->links() }}
            </div>
        </div>
        @endif

    </div>

    {{-- Lịch sử nhập kho --}}
    <div class="tab-panel p-24" id="tab-import-log">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ngày</th><th>Nguyên liệu</th><th>Số lượng nhập</th>
                        <th>Đơn giá</th><th>Tổng tiền</th><th>Người nhập</th><th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($importLog as $log)
                    <tr>
                        <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d/m/Y') : '—' }}</td>
                        <td><strong>{{ $log->nguyenLieu->ten_nguyen_lieu ?? '—' }}</strong></td>
                        <td>{{ $log->so_luong }} {{ $log->nguyenLieu->don_vi_tinh ?? '' }}</td>
                        <td>
                            @if($log->don_gia !== null)
                                {{ number_format($log->don_gia, 0, ',', '.') }}đ/{{ $log->nguyenLieu->don_vi_tinh ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <strong>
                                @if($log->don_gia !== null)
                                    {{ number_format($log->don_gia * $log->so_luong, 0, ',', '.') }}đ
                                @else
                                    —
                                @endif
                            </strong>
                        </td>
                        <td>{{ $log->nguoiTao->ho_ten ?? '—' }}</td>
                        <td class="text-muted">{{ $log->ghi_chu ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty-state-sm">
                            Chưa có lịch sử nhập kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Lịch sử xuất kho --}}
    <div class="tab-panel p-24" id="tab-export-log">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ngày</th><th>Nguyên liệu</th><th>Số lượng xuất</th>
                        <th>Lý do</th><th>Người xuất</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exportLog as $log)
                    <tr>
                        <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d/m/Y') : '—' }}</td>
                        <td><strong>{{ $log->nguyenLieu->ten_nguyen_lieu ?? '—' }}</strong></td>
                        <td>{{ $log->so_luong }} {{ $log->nguyenLieu->don_vi_tinh ?? '' }}</td>
                        <td>{{ $log->ghi_chu ?: '—' }}</td>
                        <td>{{ $log->nguoiTao->ho_ten ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state-sm">
                            Chưa có lịch sử xuất kho.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
