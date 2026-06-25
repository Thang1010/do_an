<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    private function resolveTargetUrl(DatabaseNotification $notification): string
    {
        $data = (array) $notification->data;

        if (! empty($data['target_url']) && is_string($data['target_url'])) {
            return $data['target_url'];
        }

        if (! empty($data['order_id'])) {
            return route('manager.orders.show', ['id' => $data['order_id']]);
        }

        if (! empty($data['table_id'])) {
            return route('manager.tables.show', ['id' => $data['table_id']]);
        }

        return route('manager.dashboard');
    }

    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('manager.notifications.index', compact('notifications'));
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return redirect($this->resolveTargetUrl($notification));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    /**
     * Trả JSON cho polling: số thông báo chưa đọc + HTML danh sách gần đây.
     */
    public function poll(Request $request)
    {
        $user = $request->user();

        $recentNotifications = $user->notifications()->latest()->limit(8)->get();
        $count = $user->unreadNotifications()->count();

        $html = view('partials.notification-items', [
            'recentNotifications' => $recentNotifications,
            'openRoute' => 'manager.notifications.open',
        ])->render();

        return response()->json([
            'count' => $count,
            'html' => $html,
        ]);
    }
}
