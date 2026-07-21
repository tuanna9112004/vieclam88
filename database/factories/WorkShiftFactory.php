<?php

namespace Database\Factories;

use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkShift>
 */
class WorkShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('shift-????'),
            'name' => fake()->words(2, true),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
