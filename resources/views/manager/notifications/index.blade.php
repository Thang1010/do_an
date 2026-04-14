@extends('layouts.manager')

@section('title', 'Thông báo')
@section('breadcrumb', 'Tổng quan / <strong>Thông báo</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Danh sách thông báo</h1>
        <p class="page-subtitle">Theo dõi các sự kiện đặt bàn, gọi món và thanh toán</p>
    </div>
    <div class="page-actions">
        <form method="POST" action="{{ route('manager.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">Đánh dấu tất cả đã đọc</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Nội dung</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications ?? [] as $notification)
                @php
                    $data = (array) ($notification->data ?? []);
                    $title = $data['title'] ?? 'Thông báo hệ thống';
                    $message = $data['message'] ?? 'Bạn có một thông báo mới.';
                @endphp
                <tr>
                    <td><span class="font-600">{{ $title }}</span></td>
                    <td class="text-muted">{{ $message }}</td>
                    <td class="text-12 text-muted">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($notification->read_at)
                            <span class="badge badge-done">Đã đọc</span>
                        @else
                            <span class="badge badge-pending">Chưa đọc</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('manager.notifications.open', $notification->id) }}" class="btn btn-primary btn-sm">
                            Mở thông báo
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">Chưa có thông báo nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($notifications) && method_exists($notifications, 'hasPages') && $notifications->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $notifications->firstItem() }}-{{ $notifications->lastItem() }} / {{ $notifications->total() }} thông báo
            </span>
            {{ $notifications->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
