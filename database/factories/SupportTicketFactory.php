<?php

namespace Database\Factories;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPortalContext;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'portal_context' => SupportTicketPortalContext::Member,
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'category' => SupportTicketCategory::TechnicalIssueOrError,
            'status' => SupportTicketStatus::Open,
        ];
    }
}
