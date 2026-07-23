<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Ba role HR đều tạo/sửa/xem được; chỉ soft delete/restore dành riêng cho Super Admin.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return true;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->isSuperAdmin();
    }
}
