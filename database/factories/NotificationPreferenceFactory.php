<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(['email', 'sms', 'system']),
            'notification_key' => fake()->randomElement([
                'member.approved',
                'work.reviewed',
                'invoice.issued',
                'payment.received',
            ]),
            'is_enabled' => fake()->boolean(85),
        ];
    }
}