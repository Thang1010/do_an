<?php

namespace App\Providers;

use App\Models\CaLamViec;
use App\Models\DonHang;
use App\Models\NguoiDung;
use App\Policies\OrderPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // ── Đăng ký Policies ─────────────────────────────────────────
        Gate::policy(DonHang::class, OrderPolicy::class);
        Gate::policy(NguoiDung::class, UserPolicy::class);
        Gate::policy(CaLamViec::class, ShiftPolicy::class);

        // ── Đăng ký Observers ────────────────────────────────────────
        \App\Models\DanhGiaSanPham::observe(\App\Observers\DanhGiaSanPhamObserver::class);
        \App\Models\LichSuKho::observe(\App\Observers\LichSuKhoObserver::class);
    }
}
