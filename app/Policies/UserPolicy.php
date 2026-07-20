<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * ADR-067 điểm 5: chỉ Admin được reset mật khẩu, và chỉ cho Staff (không áp dụng
     * giữa Admin với nhau — ngoài phạm vi Phase 1).
     */
    public function resetPassword(User $actor, User $staff): bool
    {
        return $actor->isAdmin() && $staff->role === 'staff';
    }
}
