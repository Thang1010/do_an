@extends('manager.layout.app')

@section('title', 'Chi tiết mã giảm giá')
@section('breadcrumb')
Kinh doanh / <a href="{{ route('manager.vouchers.index') }}">Mã giảm giá & Khuyến mãi</a> / <strong>Chi tiết mã giảm giá</strong>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Chi tiết mã giảm giá {{ $voucher->ma_voucher }}</h1>
        <p class="page-subtitle">Theo dõi danh sách người dùng đã nhận mã giảm giá và trạng thái đã dùng/chưa dùng</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.vouchers.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Tổng người đã nhận</div>
        <div class="stat-value">{{ number_format($tongNguoiNhan, 0, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Đã dùng</div>
        <div class="stat-value text-success">{{ number_format($tongDaDung, 0, ',', '.') }}</div>
        <div class="stat-change">Tỷ lệ dùng: {{ number_format($tiLeDaDung, 1, ',', '.') }}%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Chưa dùng / Hết hạn</div>
        <div class="stat-value">{{ number_format($tongChuaDung, 0, ',', '.') }} / {{ number_format($tongDaHetHan, 0, ',', '.') }}</div>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Thông tin mã giảm giá</span>
    </div>
    <div class="card-body">
        <div class="grid-3">
            <div>
                <div class="text-12 text-muted">Tên chương trình</div>
                <div class="font-600">{{ $voucher->ten_voucher }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Loại giảm</div>
                <div class="font-600">{{ $voucher->loai_giam === 'phần trăm' ? 'Phần trăm' : 'Tiền mặt' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Giá trị giảm</div>
                <div class="font-600">
                    @if($voucher->loai_giam === 'phần trăm')
                        {{ rtrim(rtrim(number_format($voucher->gia_tri_giam, 2, '.', ''), '0'), '.') }}%
                    @else
                        {{ number_format($voucher->gia_tri_giam, 0, ',', '.') }}đ
                    @endif
                </div>
            </div>
            <div>
                <div class="text-12 text-muted">Đơn tối thiểu</div>
                <div class="font-600">{{ number_format((float)($voucher->don_toi_thieu ?? 0), 0, ',', '.') }}đ</div>
            </div>
            <div>
                <div class="text-12 text-muted">Bắt đầu</div>
                <div class="font-600">{{ optional($voucher->ngay_bat_dau)->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Kết thúc</div>
                <div class="font-600">{{ optional($voucher->ngay_ket_thuc)->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('manager.vouchers.show', $voucher->id) }}" class="flex-gap-10">
        <input
            type="text"
            name="search"
            class="form-control filter-search"
            placeholder="Tìm theo tên, SĐT hoặc email..."
            value="{{ request('search') }}"
        >

        <select name="trang_thai_su_dung" class="form-control w-200">
            <option value="">Tất cả trạng thái</option>
            <option value="chưa dùng" {{ request('trang_thai_su_dung') === 'chưa dùng' ? 'selected' : '' }}>Chưa dùng</option>
            <option value="đã dùng" {{ request('trang_thai_su_dung') === 'đã dùng' ? 'selected' : '' }}>Đã dùng</option>
            <option value="đã hết hạn" {{ request('trang_thai_su_dung') === 'đã hết hạn' ? 'selected' : '' }}>Đã hết hạn</option>
        </select>

        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.vouchers.show', $voucher->id) }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Danh sách người dùng đã nhận mã giảm giá</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="w-56">STT</th>
                    <th>Người dùng</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Nhận lúc</th>
                    <th>Dùng lúc</th>
                </tr>
            </thead>
            <tbody>
                @forelse($danhSachNguoiNhan as $index => $item)
                    @php
                        $isUsed = $item->trang_thai === 'đã dùng';
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="font-600">{{ $item->nguoiDung?->hoSoKhachHang?->ho_ten ?? $item->nguoiDung?->email ?? 'Không xác định' }}</td>
                        <td>{{ $item->nguoiDung->so_dien_thoai ?? '—' }}</td>
                        <td>{{ $item->nguoiDung->email ?? '—' }}</td>
                        <td>
                            @if($item->trang_thai === 'đã dùng')
                                <span class="badge badge-done">Đã dùng</span>
                            @elseif($item->trang_thai === 'đã hết hạn')
                                <span class="badge badge-inactive">Đã hết hạn</span>
                            @else
                                <span class="badge badge-default">Chưa dùng</span>
                            @endif
                        </td>
                        <td>{{ $item->duoc_cap_luc ? \Carbon\Carbon::parse($item->duoc_cap_luc)->format('d/m/Y H:i') : '—' }}</td>
                        <td>{{ $item->da_dung_luc ? \Carbon\Carbon::parse($item->da_dung_luc)->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state-sm">Chưa có người dùng nào nhận mã giảm giá này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


@endsection
