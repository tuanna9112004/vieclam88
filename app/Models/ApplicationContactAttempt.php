<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['application_id', 'contacted_by', 'channel', 'result', 'workflow_cycle', 'contacted_at', 'note'])]
class ApplicationContactAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'channel' => 'string',
            'result' => 'string',
            'contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ApplicationContactAttempt $attempt) {
            $attempt->created_at ??= now();
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }
}
