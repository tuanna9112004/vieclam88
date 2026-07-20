<?php

namespace Database\Factories;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'name' => 'Chi nhánh '.fake()->city(),
            'phone' => '09'.fake()->numerify('########'),
            'zalo' => null,
            'email' => null,
            'administrative_unit_id' => AdministrativeUnit::factory(),
            'address_detail' => fake()->streetAddress(),
            'status' => 'active',
        ];
    }
}
