<?php

namespace App\Policies;

use App\Models\CompanyContact;
use App\Models\User;

class CompanyContactPolicy
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

    public function update(User $user, CompanyContact $contact): bool
    {
        return true;
    }

    public function delete(User $user, CompanyContact $contact): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, CompanyContact $contact): bool
    {
        return $user->isSuperAdmin();
    }
}
