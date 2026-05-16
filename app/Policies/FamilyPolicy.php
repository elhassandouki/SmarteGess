<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    public function viewAny(User $user): bool { return $user->can('families.view'); }
    public function view(User $user, Family $family): bool { return $user->can('families.view'); }
    public function create(User $user): bool { return $user->can('families.create'); }
    public function update(User $user, Family $family): bool { return $user->can('families.update'); }
    public function delete(User $user, Family $family): bool { return $user->can('families.delete'); }
}

