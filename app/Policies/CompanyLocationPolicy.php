<?php

namespace App\Policies;

use App\Models\CompanyLocation;
use App\Models\User;

class CompanyLocationPolicy
{
    /**
     * hr.company-locations.* (docs/ROUTE-MAP.md mục "HR công ty"): Staff và Admin đều tạo/sửa
     * được — chỉ soft delete/restore dành riêng cho Admin (ADR-053).
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
        return $user->isAdmin();
    }

    public function restore(User $user, CompanyLocation $location): bool
    {
        return $user->isAdmin();
    }
}
