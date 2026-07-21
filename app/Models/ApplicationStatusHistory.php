<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'from_stage', 'to_stage', 'close_reason', 'workflow_cycle', 'changed_by',
    'actor_type', 'note', 'metadata',
])]
class ApplicationStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'from_stage' => 'string',
            'to_stage' => 'string',
            'close_reason' => 'string',
            'actor_type' => 'string',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ApplicationStatusHistory $history) {
            $history->created_at ??= now();
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
