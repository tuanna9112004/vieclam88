<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Toàn bộ hr.branches.* (docs/ROUTE-MAP.md) chỉ dành cho admin — Staff không quản lý Branch.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Branch $branch): bool
    {
        return false;
    }
}
