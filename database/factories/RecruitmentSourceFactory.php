<?php

namespace Database\Factories;

use App\Models\RecruitmentSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentSource>
 */
class RecruitmentSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('source-????'),
            'name' => fake()->words(2, true),
            'type' => 'website',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
