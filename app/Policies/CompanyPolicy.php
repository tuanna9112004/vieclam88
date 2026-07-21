<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * hr.companies.* (docs/ROUTE-MAP.md mục "HR công ty"): Staff và Admin đều tạo/sửa/xem được
     * — chỉ soft delete/restore dành riêng cho Admin (CORE-FLOWS.md mục 0.2, ADR-045).
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
        return $user->isAdmin();
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }
}
