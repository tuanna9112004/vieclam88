<?php

namespace App\Policies;

use App\Models\CandidateDuplicateReview;
use App\Models\User;

class CandidateDuplicateReviewPolicy
{
    /**
     * hr.duplicate-reviews.index (docs/CORE-FLOWS.md muc 6.2.2, ADR-062): chi Admin duoc xem danh sach nghi ngo trung.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * hr.duplicate-reviews.show: chi Admin duoc xem chi tiet hai candidate va form xu ly.
     */
    public function view(User $user, CandidateDuplicateReview $review): bool
    {
        return $user->isAdmin();
    }

    /**
     * hr.duplicate-reviews.resolve: chi Admin duoc phep xu ly.
     */
    public function resolve(User $user, CandidateDuplicateReview $review): bool
    {
        return $user->isAdmin();
    }
}
