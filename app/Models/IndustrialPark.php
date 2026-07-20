<?php

namespace App\Models;

use Database\Factories\IndustrialParkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['administrative_unit_id', 'name', 'slug', 'official_name', 'address_detail', 'is_active'])]
class IndustrialPark extends Model
{
    /** @use HasFactory<IndustrialParkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function administrativeUnit(): BelongsTo
    {
        return $this->belongsTo(AdministrativeUnit::class);
    }
}
