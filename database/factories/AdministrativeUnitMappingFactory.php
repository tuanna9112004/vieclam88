<?php

namespace Database\Factories;

use App\Models\AdministrativeUnit;
use App\Models\AdministrativeUnitMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeUnitMapping>
 */
class AdministrativeUnitMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'administrative_unit_id' => AdministrativeUnit::factory(),
            'province_id' => null,
            'ward_id' => null,
            'status' => 'missing',
            'reason' => 'factory default',
            'mapped_at' => null,
        ];
    }
}
