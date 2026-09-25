<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Developer,
            'status' => UserStatus::Active,
            'timezone' => 'UTC',
            'skills' => ['php', 'javascript'],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->isDeveloper()) {
                $user->developerProfile()->firstOrCreate([]);
            }
            $user->wallet()->firstOrCreate([], ['type' => 'user', 'currency' => 'USD']);
        });
    }

    public function developer(): static
    {
        return $this->state(['role' => UserRole::Developer]);
    }

    public function requester(): static
    {
        return $this->state(['role' => UserRole::Requester]);
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => UserStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => 'Test']);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
