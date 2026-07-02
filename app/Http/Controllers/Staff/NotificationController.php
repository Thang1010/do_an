<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    private function resolveTargetUrl(DatabaseNotification $notification): string
    {
        $data = (array) $notification->data;

        if (! empty($data['shift_id'])) {
            return route('staff.shifts.show', ['id' => $data['shift_id']]);
        }

        if (! empty($data['order_id'])) {
            $tableId = $data['table_id'] ?? null;
            if ($tableId) {
                return route('staff.tables.index', ['table' => $tableId]);
            }
            return route('staff.tables.index', ['assign_order' => $data['order_id']]);
        }

        if (!empty($data['target_url']) && is_string($data['target_url'])) {
            return $data['target_url'];
        }

        return route('staff.tables.index');
    }

    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('staff.notifications.index', compact('notifications'));
    }

    public function open(Request $request, string $id)
    {
        // Bảng thong_bao dùng khóa chính `ma_thong_bao` (cột `id` chỉ là accessor ảo),
        // nên phải tìm theo khóa của model thay vì where('id', ...) → tránh lỗi SQL.
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

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
        $showAll = $request->has('all');

        $query = $user->notifications()->latest();
        if (!$showAll) {
            $query->limit(8);
        }
        $recentNotifications = $query->get();
        $count = $user->unreadNotifications()->count();

        $html = view('partials.notification-items', [
            'recentNotifications' => $recentNotifications,
            'openRoute' => 'staff.notifications.open',
        ])->render();

        // Tín hiệu "có món mới thuộc đơn do QUẢN LÝ/CHỦ tạo hôm nay" — đếm trực tiếp để kêu chuông
        // ỔN ĐỊNH. Đơn do CHÍNH nhân viên tạo KHÔNG tính (không ai cần kêu); đơn khách đã có "bàn rung".
        $managerIds = \App\Models\NguoiDung::whereIn('vai_tro', ['quản lý', 'chủ cửa hàng'])->pluck('id');
        $activityCount = \App\Models\ChiTietDonHang::whereDate('created_at', today())
            ->whereHas('donHang', fn($q) => $q->whereIn('nhan_vien_id', $managerIds))
            ->count();

        return response()->json([
            'count' => $count,
            'html' => $html,
            'takeawayCount' => DonHang::takeawayQueue()->count(),
            'banRungCount' => DonHang::banRung()->distinct('ban_an_id')->count('ban_an_id'),
            'activityCount' => $activityCount,
        ]);
    }
}
