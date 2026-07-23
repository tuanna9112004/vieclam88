<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Page $page): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->isSuperAdmin();
    }
}
