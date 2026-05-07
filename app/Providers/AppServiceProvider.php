<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        Gate::define('commercial-area', function (User $user) {
            return in_array($user->role ?? 'USER', ['ADMIN', 'COMMERCIAL', 'COMPTABLE', 'USER'], true);
        });

        Gate::define('accounting-area', function (User $user) {
            return in_array($user->role ?? 'USER', ['ADMIN', 'COMPTABLE'], true);
        });
    }
}
