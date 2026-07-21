<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'type', 'scheduled_at', 'location_detail', 'status', 'outcome', 'note',
    'workflow_cycle', 'created_by', 'completed_by', 'completed_at',
])]
class ApplicationAppointment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'scheduled_at' => 'datetime',
            'status' => 'string',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
