<?php

namespace App\Policies;

use App\Models\CompanyLocation;
use App\Models\User;

class CompanyLocationPolicy
{
    /**
     * Ba role HR đều tạo/sửa được; chỉ soft delete/restore dành riêng cho Super Admin.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CompanyLocation $location): bool
    {
        return true;
    }

    public function delete(User $user, CompanyLocation $location): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, CompanyLocation $location): bool
    {
        return $user->isSuperAdmin();
    }
}
