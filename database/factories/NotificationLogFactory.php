<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['queued', 'sent', 'failed']);

        return [
            'user_id' => fake()->boolean(85) ? User::factory() : null,
            'notification_key' => fake()->randomElement([
                'member.approved',
                'work.submitted',
                'invoice.issued',
                'payment.received',
            ]),
            'channel' => fake()->randomElement(['email', 'system', 'sms']),
            'status' => $status,
            'subject' => fake()->sentence(4),
            'payload_json' => [
                'message' => fake()->sentence(),
                'meta' => ['source' => 'factory'],
            ],
            'sent_at' => $status === 'sent'
                ? fake()->dateTimeBetween('-15 days', 'now')
                : null,
            'failed_at' => $status === 'failed'
                ? fake()->dateTimeBetween('-15 days', 'now')
                : null,
            'failure_reason' => $status === 'failed'
                ? fake()->sentence()
                : null,
        ];
    }
}