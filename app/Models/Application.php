<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id', 'candidate_id', 'job_id', 'source_id', 'owner_branch_id', 'stage',
    'stage_changed_at', 'close_reason', 'workflow_cycle', 'workflow_cycle_started_at',
    'reopened_at', 'reopened_by', 'submission_token', 'needs_duplicate_review',
    'duplicate_reviewed_at', 'duplicate_reviewed_by', 'last_reapplied_at', 'submitted_full_name',
    'submitted_phone', 'submitted_phone_normalized', 'submission_snapshot', 'job_snapshot',
    'source_detail', 'utm_source', 'utm_medium', 'utm_campaign', 'landing_url',
    'consent_version', 'consent_text_hash', 'consented_at', 'consent_ip', 'consent_user_agent',
    'expected_start_at', 'started_at', 'closed_at',
])]
class Application extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'stage' => 'string',
            'stage_changed_at' => 'datetime',
            'close_reason' => 'string',
            'workflow_cycle_started_at' => 'datetime',
            'reopened_at' => 'datetime',
            'needs_duplicate_review' => 'boolean',
            'duplicate_reviewed_at' => 'datetime',
            'last_reapplied_at' => 'datetime',
            'submission_snapshot' => 'array',
            'job_snapshot' => 'array',
            'consented_at' => 'datetime',
            'expected_start_at' => 'date',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function recruitmentSource(): BelongsTo
    {
        return $this->belongsTo(RecruitmentSource::class, 'source_id');
    }

    public function ownerBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'owner_branch_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function duplicateReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'duplicate_reviewed_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function contactAttempts(): HasMany
    {
        return $this->hasMany(ApplicationContactAttempt::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ApplicationAppointment::class);
    }

    public function branchHistories(): HasMany
    {
        return $this->hasMany(ApplicationBranchHistory::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class);
    }

    public function duplicateReviews(): HasMany
    {
        return $this->hasMany(CandidateDuplicateReview::class);
    }
}
