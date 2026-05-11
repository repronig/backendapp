<?php

namespace App\Notifications\System;

use App\Models\SupportTicket;

class SupportTicketSubmittedUserSystemNotification extends BaseSystemNotification
{
    public function __construct(
        public int $supportTicketId,
        public string $subjectLine,
        public string $supportInboxPath,
    ) {}

    public function toArray(object $notifiable): array
    {
        $ref = SupportTicket::formattedReference($this->supportTicketId);

        $message = sprintf(
            'We received your support request %s: "%s". Our team will review it and reply here.',
            $ref,
            $this->subjectLine
        );

        return [
            ...$this->basePayload(
                'support_ticket_submitted_user',
                sprintf('Support request %s received', $ref),
                $message,
                'info',
                SupportTicket::portalTicketDetailPath($this->supportInboxPath, $this->supportTicketId),
                [
                    'entity_type' => 'support_ticket',
                    'entity_id' => $this->supportTicketId,
                ]
            ),
            'category' => 'support',
        ];
    }
}
