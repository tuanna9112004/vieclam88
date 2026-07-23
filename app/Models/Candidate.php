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
    'current_ward_id', 'address_detail', 'education_level', 'experience_summary', 'preferred_shift',
    'available_from', 'status', 'merged_into_candidate_id', 'merged_at', 'merged_by', 'merge_reason',
    'anonymized_at', 'anonymized_by',
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

    /**
     * TASK 1.3: nguồn địa chỉ mới, ưu tiên khi đọc — fallback currentAdministrativeUnit() cho dữ
     * liệu chưa backfill.
     */
    public function currentWard(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'current_ward_id');
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

    /**
     * docs/CORE-FLOWS.md mục 6.3 — Tìm Candidate root còn active trong merged family.
     */
    public function resolveRoot(): self
    {
        $current = $this;
        $visited = [];

        while ($current->merged_into_candidate_id !== null && ! in_array($current->id, $visited, true)) {
            $visited[] = $current->id;
            $parent = self::find($current->merged_into_candidate_id);
            if (! $parent) {
                break;
            }
            $current = $parent;
        }

        return $current;
    }

    /**
     * docs/CORE-FLOWS.md mục 6.3 — Lấy danh sách ID toàn bộ candidate trong merged family.
     *
     * @return array<int, int>
     */
    public function getMergedFamilyIds(): array
    {
        $root = $this->resolveRoot();
        $familyIds = [$root->id];
        $queue = [$root->id];

        while (! empty($queue)) {
            $currentId = array_shift($queue);
            $childrenIds = self::where('merged_into_candidate_id', $currentId)->pluck('id')->all();

            foreach ($childrenIds as $childId) {
                if (! in_array($childId, $familyIds, true)) {
                    $familyIds[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $familyIds;
    }
}
