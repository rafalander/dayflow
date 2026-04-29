<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cargoId = Cargo::query()->where('slug', 'dayflow-sys-colaborador')->value('id');

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'google_id' => 'manual-'.Str::uuid()->toString(),
            'password' => static::$password ??= Hash::make('password'),
            'cargo_id' => $cargoId ?? 1,
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
