<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin()
            || ($actor->isBranchAdmin() && $actor->hasValidBranchAssignment());
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $staff): bool
    {
        return $this->canManageStaff($actor, $staff);
    }

    public function lock(User $actor, User $staff): bool
    {
        return $this->canManageStaff($actor, $staff);
    }

    public function unlock(User $actor, User $staff): bool
    {
        return $this->canManageStaff($actor, $staff);
    }

    public function resetPassword(User $actor, User $staff): bool
    {
        return $this->canManageStaff($actor, $staff);
    }

    private function canManageStaff(User $actor, User $staff): bool
    {
        if ($actor->isSuperAdmin()) {
            return $staff->isStaff() || $staff->isBranchAdmin();
        }

        return $staff->isStaff()
            && ($actor->isBranchAdmin()
                && $actor->hasValidBranchAssignment()
                && $actor->branch_id === $staff->branch_id);
    }
}
