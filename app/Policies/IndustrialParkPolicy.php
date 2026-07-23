<?php

namespace App\Policies;

use App\Models\IndustrialPark;
use App\Models\User;

class IndustrialParkPolicy
{
    /**
     * hr.industrial-parks.* (docs/ROUTE-MAP.md) chỉ Admin quản lý được.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, IndustrialPark $industrialPark): bool
    {
        return $user->isSuperAdmin();
    }
}
