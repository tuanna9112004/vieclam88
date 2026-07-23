<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function updateAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
