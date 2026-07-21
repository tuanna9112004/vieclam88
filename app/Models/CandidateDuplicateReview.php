<?php

namespace App\Models;

use App\Enums\CandidateDuplicateReviewReason;
use App\Enums\CandidateDuplicateReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'candidate_id', 'suspected_candidate_id', 'reason_code', 'status',
    'reviewed_by', 'reviewed_at', 'review_note',
])]
class CandidateDuplicateReview extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'reason_code' => CandidateDuplicateReviewReason::class,
            'status' => CandidateDuplicateReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function suspectedCandidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'suspected_candidate_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
