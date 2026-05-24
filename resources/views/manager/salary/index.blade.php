@extends('manager.layout.app')

@section('title', 'Bảng lương')
@section('breadcrumb', 'Kho & Tài chính / Chi tiêu / <strong>Lương</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Bảng lương</h1>
        <p class="page-subtitle">Kỳ lương: {{ $periodStart->format('d/m/Y') }} → {{ $periodEnd->format('d/m/Y') }}</p>
    </div>
    <a href="{{ route('manager.salary.export', ['thang' => $thang, 'nam' => $nam]) }}" class="btn btn-secondary">Xuất Excel</a>
</div>

<div class="card mb-20">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.salary.index') }}" class="filter-bar mb-0">
            <select name="vai_tro" class="form-control" style="max-width: 180px;">
                <option value="">Tất cả vai trò</option>
                <option value="nhân viên" {{ $filterRole === 'nhân viên' ? 'selected' : '' }}>Nhân viên</option>
                <option value="quản lý" {{ $filterRole === 'quản lý' ? 'selected' : '' }}>Quản lý</option>
            </select>
            <select name="thang" class="form-control" style="max-width: 130px;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $thang == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                @endfor
            </select>
            <select name="nam" class="form-control" style="max-width: 120px;">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $nam == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <input type="text" name="search" class="form-control" placeholder="Tìm theo tên..." value="{{ $search }}" style="max-width: 220px;">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('manager.salary.index') }}" class="btn btn-secondary">Xóa lọc</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Danh sách nhân viên</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Họ Tên</th>
                    <th>Vai trò</th>
                    <th>Chức vụ</th>
                    <th>Loại hình</th>
                    <th>Tổng số giờ làm</th>
                    <th>Tổng lương</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $row)
                    <tr>
                        <td><strong>{{ $row['ho_ten'] }}</strong></td>
                        <td>
                            <span class="badge {{ $row['vai_tro'] === 'quản lý' ? 'badge-primary' : 'badge-info' }}">
                                {{ ucfirst($row['vai_tro']) }}
                            </span>
                        </td>
                        <td>{{ $row['chuc_vu'] }}</td>
                        <td>
                            <span class="badge badge-outline">{{ ucfirst($row['loai_hinh']) }}</span>
                        </td>
                        <td>{{ number_format($row['tong_gio'], 1) }} giờ</td>
                        <td class="price-text"><strong>{{ number_format($row['tong_luong'], 0, ',', '.') }}đ</strong></td>
                        <td>
                            <a href="{{ route('manager.salary.show', ['id' => $row['id'], 'thang' => $thang, 'nam' => $nam]) }}" class="btn btn-sm btn-secondary">Xem</a>
                            <a href="{{ route('manager.salary.edit', $row['id']) }}" class="btn btn-sm btn-primary">Sửa</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">Không tìm thấy nhân viên nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="card-footer">
            <div class="pagination-footer">
                <span class="pagination-info">Hiển thị {{ $users->firstItem() }}-{{ $users->lastItem() }} / {{ $users->total() }}</span>
                {{ $users->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
