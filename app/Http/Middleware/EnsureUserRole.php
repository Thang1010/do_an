<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user('nguoi_dung') ?? $request->user();

        if (! $user) {
            abort(403, 'Bạn cần đăng nhập để tiếp tục.');
        }

        if (empty($roles) || in_array($user->vai_tro, $roles, true)) {
            return $next($request);
        }

        $message = 'Bạn không có quyền truy cập chức năng này.';

        // Request API/AJAX → giữ nguyên 403 để client xử lý
        if ($request->expectsJson()) {
            abort(403, $message);
        }

        // Người dùng web: không hiện trang lỗi 403, mà quay lại trang trước
        // kèm cờ để hiển thị modal thông báo "không có quyền".
        return redirect()
            ->back(fallback: $this->homeRouteForUser($user))
            ->with('access_denied', $message);
    }

    /**
     * Trang an toàn để quay về theo vai trò khi không xác định được trang trước.
     */
    private function homeRouteForUser($user): string
    {
        $role = $user->vai_tro ?? '';

        if (in_array($role, ['quản lý', 'chủ cửa hàng'], true) && Route::has('manager.dashboard')) {
            return route('manager.dashboard');
        }

        if ($role === 'nhân viên' && Route::has('staff.dashboard')) {
            return route('staff.dashboard');
        }

        return url('/');
    }
}
