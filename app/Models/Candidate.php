<?php

namespace App\Models;

use App\Support\VietnameseNormalizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id', 'full_name', 'date_of_birth', 'gender', 'current_administrative_unit_id',
    'address_detail', 'education_level', 'experience_summary', 'preferred_shift',
    'available_from', 'status',
])]
class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => 'string',
            'available_from' => 'date',
            'status' => 'string',
            'merged_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Candidate $candidate) {
            $candidate->full_name_normalized = VietnameseNormalizer::normalize($candidate->full_name);
        });
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CandidateContact::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function currentAdministrativeUnit(): BelongsTo
    {
        return $this->belongsTo(AdministrativeUnit::class, 'current_administrative_unit_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_candidate_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    public function anonymizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anonymized_by');
    }
}
