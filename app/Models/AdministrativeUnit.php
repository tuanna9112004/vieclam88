<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'official_code', 'name', 'slug', 'type', 'is_active', 'valid_from', 'valid_to'])]
class AdministrativeUnit extends Model
{
    use HasFactory;

    public const TYPES = [
        'province',
        'city',
        'commune',
        'ward',
        'special_zone',
        'legacy_district',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function industrialParks(): HasMany
    {
        return $this->hasMany(IndustrialPark::class);
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $descendantIds = [];
        $frontier = [$this->getKey()];

        while ($frontier !== []) {
            $children = self::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id !== $this->getKey() && ! isset($descendantIds[$id]))
                ->values()
                ->all();

            foreach ($children as $childId) {
                $descendantIds[$childId] = true;
            }

            $frontier = $children;
        }

        return array_keys($descendantIds);
    }
}
