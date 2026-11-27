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
        Gate::define('admin-kpap', fn (User $user) => $user->role === User::ROLE_ADMIN_KPAP);
        Gate::define('admin-departemen', fn (User $user) => $user->role === User::ROLE_ADMIN_DEPARTEMEN);

        RateLimiter::for('pengajuan-public', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}