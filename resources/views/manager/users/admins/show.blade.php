@extends('manager.layout.app')

@section('title', 'Chi tiết người dùng')
@section('breadcrumb', 'Nhân sự / Quản lý người dùng / <strong>Chi tiết</strong>')

@section('content')
<div class="page-header">
	<div>
		<h1 class="page-title">Chi tiết người dùng</h1>
		<p class="page-subtitle">Thông tin tài khoản và hồ sơ theo vai trò</p>
	</div>
	<div class="page-actions">
		@if($user->vai_tro === 'khách hàng')
			<a href="{{ route('manager.users.customers') }}" class="btn btn-secondary">Quay lại danh sách khách hàng</a>
		@elseif($user->vai_tro === 'nhân viên')
			<a href="{{ route('manager.users.staffs') }}" class="btn btn-secondary">Quay lại danh sách nhân viên</a>
		@else
			<a href="{{ route('manager.users.admins') }}" class="btn btn-secondary">Quay lại danh sách quản lý</a>
		@endif
	</div>
</div>

<div class="grid-3 mb-4">
	<div class="stat-card">
		<div class="stat-label">Họ tên</div>
		<div class="stat-value" style="font-size:22px;">{{ $user->ho_ten }}</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Vai trò</div>
		<div class="stat-value" style="font-size:22px;">{{ ucfirst($user->vai_tro) }}</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Trạng thái</div>
		<div class="stat-value" style="font-size:22px;">{{ $user->trang_thai === 'hoạt động' ? 'Hoạt động' : 'Bị khóa' }}</div>
	</div>
</div>

<div class="card mb-4">
	<div class="card-header">
		<div class="card-title">Thông tin tài khoản</div>
	</div>
	<div class="card-body">
		<div class="grid-2">
			<div>
				<div class="text-muted text-sm">Email</div>
				<div class="font-semibold">{{ $user->email ?? '—' }}</div>
			</div>
			<div>
				<div class="text-muted text-sm">Số điện thoại</div>
				<div class="font-semibold">{{ $user->so_dien_thoai ?? '—' }}</div>
			</div>
			<div>
				<div class="text-muted text-sm">Ngày tạo tài khoản</div>
				<div class="font-semibold">{{ optional($user->created_at)->format('d/m/Y H:i') ?? '—' }}</div>
			</div>
			<div>
				<div class="text-muted text-sm">Cập nhật gần nhất</div>
				<div class="font-semibold">{{ optional($user->updated_at)->format('d/m/Y H:i') ?? '—' }}</div>
			</div>
		</div>
	</div>
</div>

@if($user->vai_tro === 'khách hàng')
	<div class="grid-2 mb-4">
		<div class="stat-card">
			<div class="stat-label">Tổng chi tiêu</div>
			<div class="stat-value">{{ number_format($user->tong_chi_tieu_tai_khoan ?? 0, 0, ',', '.') }}đ</div>
			<div class="stat-change">Dựa trên các đơn hàng đã thanh toán</div>
		</div>
		<div class="stat-card">
			<div class="stat-label">Số đơn đã thanh toán</div>
			<div class="stat-value">{{ number_format($paidOrderCount ?? 0, 0, ',', '.') }}</div>
			<div class="stat-change">Tính theo tài khoản khách hàng</div>
		</div>
	</div>

	<div class="card mb-4">
		<div class="card-header">
			<div class="card-title">Hồ sơ khách hàng</div>
		</div>
		<div class="card-body">
			<div class="grid-2">
				<div>
					<div class="text-muted text-sm">Giới tính</div>
					<div class="font-semibold">{{ $user->hoSoKhachHang?->gioi_tinh ?? '—' }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Ngày sinh</div>
					<div class="font-semibold">{{ optional($user->hoSoKhachHang?->ngay_sinh)->format('d/m/Y') ?? '—' }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Địa chỉ</div>
					<div class="font-semibold">{{ $user->hoSoKhachHang?->dia_chi ?? '—' }}</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<div class="card-title">10 đơn đã thanh toán gần nhất</div>
		</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr>
						<th>Mã đơn</th>
						<th>Loại đơn</th>
						<th>Tổng tiền</th>
						<th>Ngày tạo</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@forelse($recentPaidOrders as $order)
						<tr>
							<td class="font-semibold">{{ $order->ma_don_hang ?? ('#' . $order->id) }}</td>
							<td>{{ $order->loai_don ?? '—' }}</td>
							<td>{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</td>
							<td>{{ optional($order->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
							<td>
								<a href="{{ route('manager.orders.show', $order->id) }}" class="btn btn-secondary btn-sm">Xem đơn</a>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="5" class="empty-state">Khách hàng chưa có đơn đã thanh toán.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
@elseif($user->vai_tro === 'nhân viên')
	<div class="card">
		<div class="card-header">
			<div class="card-title">Hồ sơ nhân viên</div>
		</div>
		<div class="card-body">
			<div class="grid-2">
				<div>
					<div class="text-muted text-sm">Mã nhân viên</div>
					<div class="font-semibold">{{ $user->hoSoNhanVien?->ma_nhan_vien ?? '—' }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Chức vụ</div>
					<div class="font-semibold">{{ $user->hoSoNhanVien?->chucVu?->ten_chuc_vu ?? '—' }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Lương cơ bản</div>
					<div class="font-semibold">{{ number_format($user->hoSoNhanVien?->luong_co_ban ?? 0, 0, ',', '.') }}đ</div>
				</div>
				<div>
					<div class="text-muted text-sm">Ngày vào làm</div>
					<div class="font-semibold">{{ optional($user->hoSoNhanVien?->ngay_vao_lam)->format('d/m/Y') ?? '—' }}</div>
				</div>
			</div>
		</div>
	</div>
@else
	<div class="card">
		<div class="card-header">
			<div class="card-title">Hồ sơ quản lý</div>
		</div>
		<div class="card-body">
			<div class="grid-2">
				<div>
					<div class="text-muted text-sm">Mã quản lý</div>
					<div class="font-semibold">{{ $user->hoSoQuanLy?->ma_quan_ly ?? '—' }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Chức vụ quản lý</div>
					<div class="font-semibold">{{ $user->hoSoQuanLy?->chucVu?->ten_chuc_vu ?? '—' }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Loại hình làm việc</div>
					<div class="font-semibold">{{ ucfirst($user->hoSoQuanLy?->loai_hinh_lam_viec ?? 'Toàn thời gian') }}</div>
				</div>
				<div>
					<div class="text-muted text-sm">Ngày vào làm</div>
					<div class="font-semibold">{{ optional($user->hoSoQuanLy?->ngay_vao_lam)->format('d/m/Y') ?? '—' }}</div>
				</div>
			</div>
		</div>
	</div>
@endif
@endsection
