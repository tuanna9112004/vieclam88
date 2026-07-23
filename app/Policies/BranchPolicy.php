<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isBranchAdmin() && $user->hasValidBranchAssignment());
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->canManageBranch($branch);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->canManageBranch($branch);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Branch $branch): bool
    {
        return false;
    }
}
