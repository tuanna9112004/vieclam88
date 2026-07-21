<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'candidate_id', 'type', 'value', 'normalized_value', 'is_primary', 'is_verified',
    'verified_at', 'is_active',
])]
class CandidateContact extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
