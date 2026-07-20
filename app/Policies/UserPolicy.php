<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * hr.staff.* (docs/ROUTE-MAP.md) chỉ Admin quản lý được — và chỉ quản lý Staff, không
     * áp dụng giữa Admin với nhau (khớp phạm vi resetPassword, ADR-067 điểm 5).
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $staff): bool
    {
        return $actor->isAdmin() && $staff->role === 'staff';
    }

    public function lock(User $actor, User $staff): bool
    {
        return $actor->isAdmin() && $staff->role === 'staff';
    }

    public function unlock(User $actor, User $staff): bool
    {
        return $actor->isAdmin() && $staff->role === 'staff';
    }

    public function resetPassword(User $actor, User $staff): bool
    {
        return $actor->isAdmin() && $staff->role === 'staff';
    }
}
