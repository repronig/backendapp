<?php

namespace App\Mail\Support;

use App\Mail\BaseAppMailable;
use App\Models\SupportTicket;

class SupportTicketSubmittedAdminMailable extends BaseAppMailable
{
    public function __construct(public SupportTicket $supportTicket, public string $ticketUrl) {}

    protected function subjectLine(): string
    {
        return sprintf('New support ticket %s — REPRONIG', SupportTicket::formattedReference($this->supportTicket->id));
    }

    protected function viewName(): string
    {
        return 'emails.support.ticket-submitted-admin';
    }

    protected function viewData(): array
    {
        return [
            'ticket' => $this->supportTicket->fresh(['user']),
            'platformUrl' => rtrim((string) config('app.frontend_url'), '/'),
            'ticketUrl' => $this->ticketUrl,
        ];
    }
}
