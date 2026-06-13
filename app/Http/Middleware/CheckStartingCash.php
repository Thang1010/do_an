<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CaLamViec;
use App\Models\ChotCa;

class CheckStartingCash
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        $routeName = $request->route() ? $request->route()->getName() : null;
        $protectedRoutes = [
            'manager.inventory.import.store',
            'manager.inventory.export.store',
            'manager.expenses.store',
            'staff.expenses.store',
        ];

        if (!in_array($routeName, $protectedRoutes)) {
            return $next($request);
        }

        if (!in_array($user->vai_tro, ['quản lý', 'nhân viên'])) {
            return $next($request);
        }

        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $shift = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', $today)
            ->where('gio_bat_dau', '<=', $currentTime)
            ->where('gio_ket_thuc', '>=', $currentTime)
            ->first();

        if (!$shift) {
            $shift = CaLamViec::where('nguoi_dung_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->orderBy('gio_bat_dau')
                ->first();
        }

        if ($shift) {
            $caIds = CaLamViec::where('ngay_lam', $shift->ngay_lam)
                ->where('ten_ca', $shift->ten_ca)
                ->pluck('id');
            $hasTienDauCa = ChotCa::whereIn('ca_lam_viec_id', $caIds)->exists();

            if (!$hasTienDauCa) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'Vui lòng nhập tiền đầu ca trước.',
                        'needs_start_cash' => $shift->id
                    ], 403);
                }
                return back()->with('needs_start_cash_for_shift', $shift->id);
            }
        }

        return $next($request);
    }
}
