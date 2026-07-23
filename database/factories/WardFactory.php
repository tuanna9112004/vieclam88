<?php

namespace Database\Factories;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'code' => (string) fake()->unique()->numberBetween(100, 99999),
            'name' => fake()->unique()->streetName(),
            'is_active' => true,
        ];
    }
}
