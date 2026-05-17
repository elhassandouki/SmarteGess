<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantResolver;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            if (($model->tenant_id ?? null) !== null) {
                return;
            }

            $tenantId = app(TenantResolver::class)->tenantId();
            if ($tenantId !== null) {
                $model->tenant_id = $tenantId;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(TenantResolver::class)->tenantId();
            $enforce = (bool) config('tenancy.enforce_tenant_scope', false);

            if ($tenantId === null && !$enforce) {
                return;
            }

            if ($tenantId === null && $enforce) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
        });
    }
}
