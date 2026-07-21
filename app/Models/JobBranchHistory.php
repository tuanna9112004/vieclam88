<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'from_branch_id', 'to_branch_id', 'reason', 'changed_by'])]
class JobBranchHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (JobBranchHistory $history) {
            $history->created_at ??= now();
        });
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
