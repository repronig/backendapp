<?php

namespace App\Mail\Support;

use App\Mail\BaseAppMailable;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;

class SupportTicketUserReplyAdminMailable extends BaseAppMailable
{
    public function __construct(
        public SupportTicket $supportTicket,
        public SupportTicketReply $reply,
        public string $ticketUrl,
    ) {}

    protected function subjectLine(): string
    {
        return sprintf('New reply on ticket %s', SupportTicket::formattedReference($this->supportTicket->id));
    }

    protected function viewName(): string
    {
        return 'emails.support.ticket-user-reply-admin';
    }

    protected function viewData(): array
    {
        return [
            'ticket' => $this->supportTicket->fresh(['user']),
            'reply' => $this->reply->fresh(['user']),
            'platformUrl' => rtrim((string) config('app.frontend_url'), '/'),
            'ticketUrl' => $this->ticketUrl,
        ];
    }
}
