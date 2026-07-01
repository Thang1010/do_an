{{-- Danh sách thông báo dùng chung cho dropdown chuông (manager + staff). --}}
{{-- Tham số: $recentNotifications (Collection), $openRoute (tên route mở 1 thông báo). --}}
@php
    $groupedNotifications = $recentNotifications->groupBy(function ($notification) {
        if ($notification->created_at) {
            $date = \Carbon\Carbon::parse($notification->created_at);
            if ($date->isToday()) {
                return 'Hôm nay (' . $date->format('d/m/Y') . ')';
            } elseif ($date->isYesterday()) {
                return 'Hôm qua (' . $date->format('d/m/Y') . ')';
            } else {
                return $date->format('d/m/Y');
            }
        }
        return 'Khác';
    });
@endphp

@forelse($groupedNotifications as $date => $items)
    <div class="notification-date-header" style="font-size: 11px; font-weight: 700; color: #7a6555; background: #f8f5ef; padding: 6px 12px; border-bottom: 1px solid #e2d9c8; border-top: 1px solid #e2d9c8; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: space-between;">
        <span>{{ $date }}</span>
    </div>
    @foreach($items as $notification)
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
                {{ optional($notification->created_at)->format('H:i') }}
            </div>
        </a>
    @endforeach
@empty
    <div class="notification-empty">Chưa có thông báo nào.</div>
@endforelse
