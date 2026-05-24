@extends('staff.layout.app')
@section('title', 'Cài đặt')
@section('breadcrumb', 'Hệ thống / <strong>Cài đặt</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Cài đặt tài khoản</h1>
        <p class="page-subtitle">Thông tin cá nhân</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Thông tin cá nhân</span>
        </div>
        <div class="card-body">
            <div class="flex-col-10">
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Họ tên</span>
                    <span class="font-600">{{ auth()->user()->ho_ten ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Email</span>
                    <span class="font-600">{{ auth()->user()->email ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Số điện thoại</span>
                    <span class="font-600">{{ auth()->user()->so_dien_thoai ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Vai trò</span>
                    <span class="badge badge-brew">{{ auth()->user()->vai_tro ?? '—' }}</span>
                </div>
                <div class="flex-center-between">
                    <span class="text-12 text-muted">Trạng thái</span>
                    <span class="badge badge-active">{{ auth()->user()->trang_thai ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
