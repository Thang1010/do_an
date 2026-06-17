@extends('staff.layout.app')
@section('title', 'Chi tiêu')
@section('breadcrumb')
Nhân viên / <strong>Chi tiêu</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Ghi nhận Chi tiêu</h1>
        <p class="page-subtitle">Chi tiêu mua nguyên liệu, vật tư trong ca làm việc</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.expenses.create') }}" class="btn btn-primary">Thêm chi tiêu</a>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('staff.expenses.index') }}" class="flex-gap-10">
        <select name="ca_lam_viec_id" class="form-control" style="width: auto;">
            <option value="">-- Tất cả ca --</option>
            @forelse($shiftOptions as $shift)
                <option value="{{ $shift->id }}" {{ (string) ($filters['ca_lam_viec_id'] ?? '') === (string) $shift->id ? 'selected' : '' }}>
                    {{ optional($shift->ngay_lam)->format('d/m/Y') }} • {{ $shift->ten_ca }} ({{ $shift->gio_bat_dau }} - {{ $shift->gio_ket_thuc }})
                </option>
            @empty
                <option value="" disabled>Chưa có ca làm việc</option>
            @endforelse
        </select>
        <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}" style="max-width: 170px;" title="Từ ngày">
        <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}" style="max-width: 170px;" title="Đến ngày">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('staff.expenses.index', ['clear' => 1]) }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

{{-- Expense History --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Lịch sử chi tiêu</span>
        <span class="text-12 text-muted">{{ $expenses->total() ?? 0 }} khoản chi</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Nguyên liệu</th>
                    <th>Số lượng</th>
                    <th>Giá nhập / sản phẩm</th>
                    <th>Thành tiền</th>
                    <th>Thanh toán</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $i => $exp)
                @php
                    $qty = $exp->lichSuKho?->so_luong;
                    $price = $exp->lichSuKho?->gia_nhap;
                    $total = ($qty !== null && $price !== null) ? $qty * $price : null;
                @endphp
                <tr>
                    <td>{{ ($expenses->firstItem() ?? 1) + $i }}</td>
                    <td class="font-600">{{ $exp->nguyenLieu?->ten_nguyen_lieu ?? '—' }}</td>
                    <td>{{ $qty !== null ? number_format($qty, 2) : '—' }} {{ $exp->nguyenLieu?->don_vi_tinh ?? '' }}</td>
                    <td>{{ $price !== null ? number_format($price, 0, ',', '.') . 'đ' : '—' }}</td>
                    <td class="price-text">{{ $total !== null ? number_format($total, 0, ',', '.') . 'đ' : '—' }}</td>
                    <td><span class="badge {{ $exp->phuong_thuc_thanh_toan === 'tiền mặt' ? 'badge-active' : 'badge-brew' }}">{{ $exp->phuong_thuc_thanh_toan }}</span></td>
                    <td class="text-muted text-12">{{ optional($exp->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state">Chưa có khoản chi nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">Hiển thị {{ $expenses->firstItem() }}-{{ $expenses->lastItem() }} / {{ $expenses->total() }}</span>
            {{ $expenses->links() }}
        </div>
    </div>
    @endif
</div>

{{-- Summary Cards --}}
<div class="grid-3 mb-20" style="margin-top: 20px;">
    <div class="stat-card">
        <div class="stat-label">Tổng chi (Tiền mặt)</div>
        <div class="stat-value">{{ number_format($summary['tong_tien_mat'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng chi (Chuyển khoản)</div>
        <div class="stat-value">{{ number_format($summary['tong_tien_chuyen_khoan'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng chi</div>
        <div class="stat-value">{{ number_format($summary['tong_chi'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
</div>
@endsection

