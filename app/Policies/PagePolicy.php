<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Page $page): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->isAdmin();
    }
}
