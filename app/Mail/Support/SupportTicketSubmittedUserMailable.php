<?php

namespace App\Mail\Support;

use App\Mail\BaseAppMailable;
use App\Models\SupportTicket;

class SupportTicketSubmittedUserMailable extends BaseAppMailable
{
    public function __construct(public SupportTicket $supportTicket, public string $supportInboxPath) {}

    protected function subjectLine(): string
    {
        return sprintf('Support request %s received', SupportTicket::formattedReference($this->supportTicket->id));
    }

    protected function viewName(): string
    {
        return 'emails.support.ticket-submitted-user';
    }

    protected function viewData(): array
    {
        $ticket = $this->supportTicket->fresh(['user']);

        $base = rtrim((string) config('app.frontend_url'), '/');

        return [
            'ticket' => $ticket,
            'platformUrl' => $base,
            'ticketUrl' => $ticket ? $base.SupportTicket::portalTicketDetailPath($this->supportInboxPath, (int) $ticket->id) : $base.$this->supportInboxPath,
        ];
    }
}
