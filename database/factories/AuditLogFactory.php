<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'member_application_submitted',
                'member_application_approved',
                'work_submitted',
                'payment_webhook_processed',
            ]),
            'subject_type' => 'App\\Models\\MemberApplication',
            'subject_id' => $this->faker->numberBetween(1, 1000),
            'before_json' => ['status' => 'draft'],
            'after_json' => ['status' => 'submitted'],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Pest Test Agent',
        ];
    }
}