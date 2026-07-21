<?php

namespace App\Policies;

use App\Models\CompanyContact;
use App\Models\User;

class CompanyContactPolicy
{
    /**
     * hr.company-contacts.* (docs/ROUTE-MAP.md mục "HR công ty"): Staff và Admin đều tạo/sửa
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

    public function update(User $user, CompanyContact $contact): bool
    {
        return true;
    }

    public function delete(User $user, CompanyContact $contact): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, CompanyContact $contact): bool
    {
        return $user->isAdmin();
    }
}
