<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => 'staff',
            'branch_id' => Branch::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'password_changed_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
            'branch_id' => null,
        ]);
    }

    /**
     * @deprecated Dùng superAdmin() cho test mới.
     */
    public function admin(): static
    {
        return $this->superAdmin();
    }

    public function branchAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'branch_admin',
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }

    public function passwordNotChanged(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_changed_at' => null,
        ]);
    }
}
