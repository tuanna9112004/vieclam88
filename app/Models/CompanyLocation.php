<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_id', 'administrative_unit_id', 'industrial_park_id', 'name', 'address_detail',
    'latitude', 'longitude', 'status',
])]
class CompanyLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function administrativeUnit(): BelongsTo
    {
        return $this->belongsTo(AdministrativeUnit::class);
    }

    public function industrialPark(): BelongsTo
    {
        return $this->belongsTo(IndustrialPark::class);
    }

    public function jobLocations(): HasMany
    {
        return $this->hasMany(JobLocation::class);
    }
}
