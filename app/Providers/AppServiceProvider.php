<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('pengajuan-public', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Hak akses Admin KPAP
        Gate::define('admin_kpap', function (User $user) {
            return $user->role === User::ROLE_ADMIN_KPAP;
        });
    }
}