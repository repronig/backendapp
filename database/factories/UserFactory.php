<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    protected static ?string $adminPinHash = null;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->optional()->firstName(),
            'last_name' => $this->faker->lastName(),
            'external_id' => (string) Str::uuid(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+234' . $this->faker->unique()->numerify('80########'),
            'password' => static::$password ??= Hash::make('password'),
            'account_type' => $this->faker->randomElement(['member', 'association_officer', 'institution_user']),
            'status' => 'active',
            'requires_two_factor' => false,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'account_type' => 'admin',
            'admin_pin_hash' => static::$adminPinHash ??= Hash::make('123456'),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'account_type' => 'super_admin',
            'admin_pin_hash' => static::$adminPinHash ??= Hash::make('123456'),
        ]);
    }
}
