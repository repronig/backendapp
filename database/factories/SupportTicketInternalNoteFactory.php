<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\SupportTicketInternalNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketInternalNote>
 */
class SupportTicketInternalNoteFactory extends Factory
{
    protected $model = SupportTicketInternalNote::class;

    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(12),
        ];
    }
}
