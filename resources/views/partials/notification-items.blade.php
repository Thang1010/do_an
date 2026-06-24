{{-- Danh sách thông báo dùng chung cho dropdown chuông (manager + staff). --}}
{{-- Tham số: $recentNotifications (Collection), $openRoute (tên route mở 1 thông báo). --}}
@forelse($recentNotifications as $notification)
    @php
        $data = (array) ($notification->data ?? []);
        $titleRaw = $data['title'] ?? 'Thông báo hệ thống';
        $messageRaw = $data['message'] ?? 'Bạn có thông báo mới.';
        $title = is_array($titleRaw)
            ? implode(', ', array_filter($titleRaw, 'strlen'))
            : (is_scalar($titleRaw) ? (string) $titleRaw : 'Thông báo hệ thống');
        $message = is_array($messageRaw)
            ? implode(', ', array_filter($messageRaw, 'strlen'))
            : (is_scalar($messageRaw) ? (string) $messageRaw : 'Bạn có thông báo mới.');
    @endphp
    <a href="{{ route($openRoute, $notification->id) }}"
        class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
        <div class="notification-item-title">{{ $title }}</div>
        <div class="notification-item-message">{{ $message }}</div>
        <div class="notification-item-time">
            {{ optional($notification->created_at)->format('d/m H:i') }}
        </div>
    </a>
@empty
    <div class="notification-empty">Chưa có thông báo nào.</div>
@endforelse
