@extends('manager.layout.app')

@section('title', 'Mã giảm giá & Khuyến mãi')
@section('breadcrumb', 'Kinh doanh / <strong>Mã giảm giá & Khuyến mãi</strong>')

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Mã giảm giá & Khuyến mãi</h1>
            <p class="page-subtitle">Quản lý mã giảm giá và chương trình ưu đãi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('manager.vouchers.create') }}" class="btn btn-primary">Tạo mã giảm giá mới</a>
        </div>
    </div>


    <div class="filter-bar">
        <form method="GET" action="{{ route('manager.vouchers.index') }}" class="flex-gap-10">
            <input type="text" name="search" class="form-control filter-search" placeholder="Tìm mã hoặc tên mã giảm giá..."
                value="{{ request('search') }}">
            <select name="trang_thai" class="form-control w-200">
                <option value="">Tất cả trạng thái</option>
                <option value="đang hoạt động" {{ request('trang_thai') === 'đang hoạt động' ? 'selected' : '' }}>Đang hoạt
                    động</option>
                <option value="ngừng phát hành" {{ request('trang_thai') === 'ngừng phát hành' ? 'selected' : '' }}>Ngừng phát
                    hành</option>
                <option value="ngưng hoạt động" {{ request('trang_thai') === 'ngưng hoạt động' ? 'selected' : '' }}>Ngưng hoạt
                    động</option>
                <option value="chưa phát hành" {{ request('trang_thai') === 'chưa phát hành' ? 'selected' : '' }}>Chưa phát
                    hành</option>
                <option value="hết hạn" {{ request('trang_thai') === 'hết hạn' ? 'selected' : '' }}>Hết hạn</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('manager.vouchers.index') }}" class="btn btn-secondary">Xóa lọc</a>
        </form>
    </div>

    {{-- Voucher table --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Danh sách mã giảm giá</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã giảm giá</th>
                        <th>Loại giảm</th>
                        <th>Giá trị</th>
                        <th>Đơn tối thiểu</th>
                        <th>Giới hạn</th>
                        <th>Bắt đầu</th>
                        <th>Hạn dùng</th>
                        <th>Trạng thái</th>
                        <th class="w-200 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        @php
                            $startDate = $voucher->ngay_bat_dau ? \Carbon\Carbon::parse($voucher->ngay_bat_dau) : null;
                            $endDate = $voucher->ngay_ket_thuc ? \Carbon\Carbon::parse($voucher->ngay_ket_thuc) : null;
                            $isNotReleased = $startDate ? now()->startOfDay()->lt($startDate->copy()->startOfDay()) : false;
                            $isExpired = $endDate ? $endDate->copy()->endOfDay()->lt(now()) : false;
                            $rawDaysLeft = $endDate && !$isExpired
                                ? (now()->diffInSeconds($endDate->copy()->endOfDay(), false) / 86400)
                                : null;
                            $daysLeft = $rawDaysLeft !== null ? max(0, (int) round($rawDaysLeft)) : null;
                            $isExpiringSoon = $daysLeft !== null && $daysLeft <= 3;
                        @endphp
                        <tr>
                            <td>
                                <span class="voucher-code">
                                    {{ $voucher->ma_voucher }}
                                </span>
                            </td>
                            <td>{{ $voucher->loai_giam === 'phần trăm' ? 'Phần trăm' : 'Tiền mặt' }}</td>
                            <td class="font-700 text-primary">
                                @if($voucher->loai_giam === 'phần trăm')
                                    {{ rtrim(rtrim(number_format($voucher->gia_tri_giam, 2, '.', ''), '0'), '.') }}%
                                @else
                                    {{ number_format($voucher->gia_tri_giam, 0, ',', '.') }}đ
                                @endif
                            </td>
                            <td>{{ number_format((float)($voucher->don_toi_thieu ?? 0), 0, ',', '.') }}đ</td>
                            <td class="text-13 text-muted">
                                {{ $voucher->so_luong ? $voucher->so_luong . ' lượt' : 'Không giới hạn' }}
                            </td>
                            <td class="text-12 text-muted">
                                {{ $startDate ? $startDate->format('d/m/Y') : '—' }}
                            </td>
                            <td class="text-12 text-muted">
                                {{ $endDate ? $endDate->format('d/m/Y') : '—' }}
                            </td>
                            <td>
                                @if($voucher->trang_thai === 'ngưng hoạt động')
                                    <span class="badge badge-inactive">Ngưng hoạt động</span>
                                @elseif($voucher->trang_thai === 'ngừng phát hành')
                                    <span class="badge badge-pending">Ngừng phát hành</span>
                                @elseif($isExpired)
                                    <span class="badge badge-inactive">Hết hạn</span>
                                @elseif($isNotReleased)
                                    <span class="badge badge-default">Chưa phát hành</span>
                                @elseif($isExpiringSoon)
                                    <span class="badge badge-pending">Sắp hết hạn</span>
                                @elseif($daysLeft !== null)
                                    <span class="badge badge-active">Còn {{ $daysLeft }} ngày</span>
                                @else
                                    <span class="badge badge-active">Đang hoạt động</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('manager.vouchers.show', $voucher->id) }}"
                                        class="btn btn-primary btn-sm">Chi tiết</a>
                                    <a href="{{ route('manager.vouchers.edit', $voucher->id) }}"
                                        class="btn btn-secondary btn-sm">Sửa</a>
                                    @php $daCap = ($voucher->voucher_nguoi_dung_count ?? 0) > 0; @endphp
                                    @if($daCap && $voucher->trang_thai === 'ngừng phát hành')
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>Đã ngừng phát</button>
                                    @else
                                        <form method="POST" action="{{ route('manager.vouchers.destroy', $voucher->id) }}"
                                            onsubmit="return confirmDelete(this, '{{ $daCap ? 'Ngừng phát hành mã ' . $voucher->ma_voucher . '? Sẽ dừng phát thêm, người đã nhận vẫn dùng được đến khi hết hạn.' : 'Xóa mã giảm giá ' . $voucher->ma_voucher . '?' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">{{ $daCap ? 'Ngừng phát' : 'Xóa' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-state">
                                Chưa có mã giảm giá nào. <a href="{{ route('manager.vouchers.create') }}" class="link-primary">Tạo ngay</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @include('manager.partials.pager', ['paginator' => $vouchers, 'label' => 'mã giảm giá'])
    </div>

    {{-- Stats --}}
    <div class="grid-3 mb-20" style="margin-top: 40px;">
        <div class="stat-card">
            <div class="stat-label">Mã giảm giá đang hoạt động</div>
            <div class="stat-value">{{ $activeVouchers }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Tổng giảm giá hôm nay</div>
            <div class="stat-value">{{ number_format($discountToday, 0, ',', '.') }}đ</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Mã giảm giá sắp hết hạn</div>
            <div class="stat-value text-danger">{{ $expiringSoon }}</div>
        </div>
    </div>

@endsection