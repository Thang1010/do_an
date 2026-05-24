<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        abort(403, 'Bạn không có quyền truy cập chức năng này.');
    }
}
