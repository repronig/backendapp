<?php

namespace Database\Factories;

use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<SecurityChallenge>
 */
class SecurityChallengeFactory extends Factory
{
    protected $model = SecurityChallenge::class;

    public function definition(): array
    {
        $code = (string) fake()->numberBetween(100000, 999999);

        return [
            'user_id' => fake()->boolean(70) ? User::factory() : null,
            'email' => fake()->safeEmail(),
            'purpose' => fake()->randomElement([
                'login_step_up',
                'password_reset',
                'email_verification',
                'sensitive_action_confirmation',
            ]),
            'code_hash' => Hash::make($code),
            'attempts' => fake()->numberBetween(0, 3),
            'expires_at' => now()->addMinutes(fake()->numberBetween(5, 30)),
            'consumed_at' => null,
            'metadata_json' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
        ];
    }

    public function consumed(): static
    {
        return $this->state(fn () => [
            'consumed_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}