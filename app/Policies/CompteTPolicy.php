<?php

namespace App\Policies;

use App\Models\CompteT;
use App\Models\User;

class CompteTPolicy
{
    public function viewAny(User $user): bool { return $user->can('tiers.view'); }
    public function view(User $user, CompteT $tier): bool { return $user->can('tiers.view'); }
    public function create(User $user): bool { return $user->can('tiers.create'); }
    public function update(User $user, CompteT $tier): bool { return $user->can('tiers.update'); }
    public function delete(User $user, CompteT $tier): bool { return $user->can('tiers.delete'); }
}

