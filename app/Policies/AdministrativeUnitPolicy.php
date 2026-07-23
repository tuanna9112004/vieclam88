<?php

namespace App\Policies;

use App\Models\AdministrativeUnit;
use App\Models\User;

class AdministrativeUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AdministrativeUnit $administrativeUnit): bool
    {
        return $user->isAdmin();
    }
}
