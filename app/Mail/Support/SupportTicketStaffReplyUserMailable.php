<?php

namespace App\Mail\Support;

use App\Mail\BaseAppMailable;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;

class SupportTicketStaffReplyUserMailable extends BaseAppMailable
{
    public function __construct(
        public SupportTicket $supportTicket,
        public SupportTicketReply $reply,
        public string $supportInboxPath,
    ) {}

    protected function subjectLine(): string
    {
        return sprintf('REPRONIG replied to your ticket %s', SupportTicket::formattedReference($this->supportTicket->id));
    }

    protected function viewName(): string
    {
        return 'emails.support.ticket-staff-reply-user';
    }

    protected function viewData(): array
    {
        $ticket = $this->supportTicket->fresh(['user']);
        $reply = $this->reply->fresh(['user']);

        $base = rtrim((string) config('app.frontend_url'), '/');

        return [
            'ticket' => $ticket,
            'reply' => $reply,
            'platformUrl' => $base,
            'ticketUrl' => $ticket ? $base.SupportTicket::portalTicketDetailPath($this->supportInboxPath, (int) $ticket->id) : $base.$this->supportInboxPath,
        ];
    }
}
