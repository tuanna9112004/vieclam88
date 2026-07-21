<?php

namespace App\Actions\Candidate;

use App\Enums\CandidateDuplicateReviewReason;
use App\Models\Candidate;

readonly class CandidateMatchResult
{
    /**
     * @param  array<int, array{candidate: Candidate, reason: CandidateDuplicateReviewReason}>  $suspectedRoots
     */
    public function __construct(
        public Candidate $candidate,
        public bool $isNew,
        public array $suspectedRoots,
    ) {
    }
}
