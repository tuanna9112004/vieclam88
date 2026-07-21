<?php

namespace Database\Factories;

use App\Models\AdministrativeUnit;
use App\Models\Company;
use App\Models\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyLocation>
 */
class CompanyLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'administrative_unit_id' => AdministrativeUnit::factory(),
            'industrial_park_id' => null,
            'name' => 'Nhà máy '.fake()->city(),
            'address_detail' => fake()->streetAddress(),
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ];
    }

    /**
     * Quick Create tối thiểu (CORE-FLOWS.md mục 0.3): chỉ biết tên, chưa xác định tỉnh/địa chỉ.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'administrative_unit_id' => null,
            'address_detail' => null,
        ]);
    }
}
