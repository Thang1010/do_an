@extends('manager.layout.app')

@section('title', 'Quản lý chi tiêu')
@section('breadcrumb', 'Kho & Tài chính / <strong>Quản lý chi tiêu</strong>')

@section('content')
@php
    $selectedShiftLabel = 'Chưa chọn ca';
    if ($selectedShift) {
        $selectedShiftLabel = sprintf(
            '%s (%s - %s)',
            $selectedShift->ten_ca,
            $selectedShift->gio_bat_dau,
            $selectedShift->gio_ket_thuc
        );
    }
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý chi tiêu</h1>
        <p class="page-subtitle">Theo dõi chi tiêu theo ca làm việc • {{ $selectedShiftLabel }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.expenses.create', ['ca_lam_viec_id' => $selectedShiftId]) }}" class="btn btn-primary">Thêm chi tiêu</a>
    </div>
</div>

<div class="card mb-12">
    <div class="card-body">
        @if($shiftGroups->isEmpty())
            <div class="alert alert-warning">Chưa có ca làm việc để quản lý chi tiêu.</div>
        @else
            <form method="GET" action="{{ route('manager.expenses.index') }}" class="filter-bar mb-0">
                <select name="ca_lam_viec_id" class="form-control" style="width: auto;">
                    <option value="">-- Tất cả ca --</option>
                    @foreach($shiftGroups as $group)
                        <option value="{{ $group->id }}" {{ (string) $selectedShiftId === (string) $group->id ? 'selected' : '' }}>
                            {{ $group->ngay_lam }} • {{ $group->ten_ca }} ({{ $group->gio_bat_dau }} - {{ $group->gio_ket_thuc }})
                        </option>
                    @endforeach
                </select>
                                <input type="date" name="ngay_lam" class="form-control" value="{{ $filterDate ?? '' }}" style="max-width: 150px;">
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="{{ route('manager.expenses.index') }}" class="btn btn-secondary">Xóa lọc</a>
            </form>
            @if(!$selectedShiftId)
                <div class="alert alert-warning mt-10" style="margin-top: 10px;">Không có ca làm việc nào đang diễn ra trong ngày hôm nay. Vui lòng chọn một ca khác để quản lý.</div>
            @endif
        @endif
    </div>
</div>





<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Danh sách chi tiêu</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Tên nguyên liệu</th>
                    <th>Mục đích</th>
                    <th>Số lượng</th>
                    <th>Giá nhập / sản phẩm</th>
                    <th>Thành tiền</th>
                    <th>Thanh toán</th>
                    <th>Người ghi nhận</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    @php
                        $history = $expense->lichSuKho;
                        $quantity = $history?->so_luong;
                        $unitPrice = $history?->gia_nhap;
                        $totalCost = ($quantity !== null && $unitPrice !== null)
                            ? ((float) $quantity * (float) $unitPrice)
                            : null;
                    @endphp
                    <tr>
                        <td class="text-12 text-muted">{{ optional($expense->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><strong>{{ optional($expense->nguyenLieu)->ten_nguyen_lieu ?? '—' }}</strong></td>
                        <td>{{ optional($expense->nguyenLieu)->muc_dich_su_dung ?? '—' }}</td>
                        <td>{{ $quantity !== null ? number_format((float) $quantity, 2, ',', '.') : '—' }} {{ optional($expense->nguyenLieu)->don_vi_tinh }}</td>
                        <td class="price-text">{{ $unitPrice !== null ? number_format((float) $unitPrice, 0, ',', '.') . 'đ' : '—' }}</td>
                        <td class="price-text">{{ $totalCost !== null ? number_format($totalCost, 0, ',', '.') . 'đ' : '—' }}</td>
                        <td>{{ $expense->phuong_thuc_thanh_toan }}</td>
                        <td>{{ $expense->nguoiTao->ho_ten ?? '—' }}</td>
                        <td class="text-muted">{{ $expense->ghi_chu ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">Chưa có khoản chi nào cho ca này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($expenses->hasPages())
        <div class="card-footer">
            <div class="pagination-footer">
                <span class="pagination-info">Hiển thị {{ $expenses->firstItem() }}-{{ $expenses->lastItem() }} / {{ $expenses->total() }} khoản</span>
                {{ $expenses->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Tổng chi (tiền mặt)</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_tien_mat'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng chi (chuyển khoản)</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_tien_chuyen_khoan'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng chi</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_chi'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
</div>
@endsection


