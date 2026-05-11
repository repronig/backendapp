<?php

namespace App\Notifications\System;

use App\Models\SupportTicket;

class SupportTicketSubmittedAdminSystemNotification extends BaseSystemNotification
{
    public function __construct(
        public string $submitterDisplayName,
        public int $supportTicketId,
        public string $subjectLine,
        public string $actionUrl,
    ) {}

    public function toArray(object $notifiable): array
    {
        $ref = SupportTicket::formattedReference($this->supportTicketId);

        $message = sprintf(
            '%s opened ticket %s: "%s".',
            $this->submitterDisplayName,
            $ref,
            $this->subjectLine
        );

        return [
            ...$this->basePayload(
                'support_ticket_submitted_admin',
                sprintf('New ticket %s', $ref),
                $message,
                'info',
                SupportTicket::portalTicketDetailPath($this->actionUrl, $this->supportTicketId),
                [
                    'entity_type' => 'support_ticket',
                    'entity_id' => $this->supportTicketId,
                ]
            ),
            'category' => 'support',
        ];
    }
}
