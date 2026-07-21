<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'from_status', 'to_status', 'reason', 'changed_by'])]
class JobStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'from_status' => 'string',
            'to_status' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JobStatusHistory $history) {
            $history->created_at ??= now();
        });
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
