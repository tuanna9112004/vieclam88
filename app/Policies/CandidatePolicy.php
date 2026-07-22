<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    /**
     * docs/CORE-FLOWS.md mục 6.4:
     * Staff chỉ mở được Candidate khi merged family có ít nhất 1 Application thuộc branch của Staff. Admin xem thoải mái.
     */
    public function view(User $user, Candidate $candidate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $familyIds = $candidate->getMergedFamilyIds();

        return Application::whereIn('candidate_id', $familyIds)
            ->where('owner_branch_id', $user->branch_id)
            ->exists();
    }

    /**
     * docs/CORE-FLOWS.md mục 6.3: Candidate đã merged không được sửa.
     */
    public function update(User $user, Candidate $candidate): bool
    {
        if ($candidate->status === 'merged' || $candidate->status === 'anonymized') {
            return false;
        }

        return $this->view($user, $candidate);
    }

    /**
     * docs/CORE-FLOWS.md mục 6.3: Chỉ Admin được merge candidate.
     */
    public function merge(User $user, Candidate $candidate): bool
    {
        return $user->isAdmin();
    }

    /**
     * docs/CORE-FLOWS.md mục 7.3: Chỉ Admin được ẩn danh (anonymize) candidate.
     */
    public function anonymize(User $user, Candidate $candidate): bool
    {
        return $user->isAdmin();
    }
}
