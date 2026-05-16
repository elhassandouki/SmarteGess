<?php

namespace App\Providers;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
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

        Gate::before(function (User $user, string $ability) {
            if (method_exists($user, 'hasRole') && ($user->hasRole('ADMIN') || $user->hasRole('admin'))) {
                return true;
            }

            if (strtoupper((string) ($user->role ?? '')) === 'ADMIN') {
                return true;
            }

            return null;
        });

        Gate::define('view-erp', function (User $user) {
            return in_array(strtoupper((string) ($user->role ?? 'USER')), ['ADMIN', 'COMMERCIAL', 'COMPTABLE', 'MAGASINIER', 'USER'], true);
        });

        Gate::define('commercial-area', function (User $user) {
            return in_array(strtoupper((string) ($user->role ?? 'USER')), ['ADMIN', 'COMMERCIAL', 'COMPTABLE', 'USER'], true);
        });

        Gate::define('accounting-area', function (User $user) {
            return in_array(strtoupper((string) ($user->role ?? 'USER')), ['ADMIN', 'COMPTABLE'], true);
        });

        Gate::define('stock-area', function (User $user) {
            return in_array(strtoupper((string) ($user->role ?? 'USER')), ['ADMIN', 'MAGASINIER', 'COMPTABLE'], true);
        });

        Gate::define('master-data-area', function (User $user) {
            return in_array(strtoupper((string) ($user->role ?? 'USER')), ['ADMIN', 'COMMERCIAL', 'COMPTABLE'], true);
        });

        $permissionMatrix = [
            'families.view' => ['ADMIN', 'COMMERCIAL', 'COMPTABLE'],
            'families.create' => ['ADMIN', 'COMMERCIAL'],
            'families.update' => ['ADMIN', 'COMMERCIAL'],
            'families.delete' => ['ADMIN'],
            'articles.view' => ['ADMIN', 'COMMERCIAL', 'COMPTABLE', 'MAGASINIER'],
            'articles.create' => ['ADMIN', 'COMMERCIAL'],
            'articles.update' => ['ADMIN', 'COMMERCIAL'],
            'articles.delete' => ['ADMIN'],
            'tiers.view' => ['ADMIN', 'COMMERCIAL', 'COMPTABLE'],
            'tiers.create' => ['ADMIN', 'COMMERCIAL'],
            'tiers.update' => ['ADMIN', 'COMMERCIAL'],
            'tiers.delete' => ['ADMIN'],
            'transporteurs.view' => ['ADMIN', 'COMMERCIAL', 'COMPTABLE'],
            'transporteurs.create' => ['ADMIN', 'COMMERCIAL'],
            'transporteurs.update' => ['ADMIN', 'COMMERCIAL'],
            'transporteurs.delete' => ['ADMIN'],
            'depots.view' => ['ADMIN', 'COMPTABLE', 'MAGASINIER'],
            'depots.create' => ['ADMIN', 'COMPTABLE'],
            'depots.update' => ['ADMIN', 'COMPTABLE'],
            'depots.delete' => ['ADMIN'],
            'documents.view' => ['ADMIN', 'COMMERCIAL', 'COMPTABLE'],
            'documents.create' => ['ADMIN', 'COMMERCIAL'],
            'documents.update' => ['ADMIN', 'COMMERCIAL'],
            'documents.delete' => ['ADMIN'],
            'documents.duplicate' => ['ADMIN', 'COMMERCIAL'],
            'documents.status' => ['ADMIN', 'COMMERCIAL', 'COMPTABLE'],
            'stocks.view' => ['ADMIN', 'COMPTABLE', 'MAGASINIER'],
            'stocks.adjust' => ['ADMIN', 'MAGASINIER'],
            'reglements.view' => ['ADMIN', 'COMPTABLE'],
            'reglements.create' => ['ADMIN', 'COMPTABLE'],
            'reglements.delete' => ['ADMIN', 'COMPTABLE'],
            'access.roles.view' => ['ADMIN'],
            'access.roles.create' => ['ADMIN'],
            'access.roles.update' => ['ADMIN'],
            'access.roles.delete' => ['ADMIN'],
            'access.permissions.view' => ['ADMIN'],
            'access.permissions.create' => ['ADMIN'],
            'access.permissions.update' => ['ADMIN'],
            'access.permissions.delete' => ['ADMIN'],
        ];

        foreach ($permissionMatrix as $ability => $roles) {
            Gate::define($ability, function (User $user) use ($ability, $roles) {
                if (method_exists($user, 'hasPermissionTo')) {
                    try {
                        if ($user->hasPermissionTo($ability)) {
                            return true;
                        }
                    } catch (PermissionDoesNotExist) {
                        // Fallback to legacy role column when permission rows are not seeded yet.
                    }
                }

                return in_array(strtoupper((string) ($user->role ?? 'USER')), $roles, true);
            });
        }
    }
}
