<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access.roles.view');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->can('access.roles.update');
    }
}
