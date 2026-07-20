<?php

namespace Database\Factories;

use App\Models\AdministrativeUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdministrativeUnit>
 */
class AdministrativeUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'parent_id' => null,
            'official_code' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'type' => 'province',
            'is_active' => true,
        ];
    }
}
