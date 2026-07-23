<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->isSuperAdmin();
    }
}
