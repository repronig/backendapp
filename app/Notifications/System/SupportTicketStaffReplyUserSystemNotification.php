<?php

namespace App\Notifications\System;

use App\Models\SupportTicket;

class SupportTicketStaffReplyUserSystemNotification extends BaseSystemNotification
{
    public function __construct(
        public int $supportTicketId,
        public string $subjectLine,
        public string $supportInboxPath,
        public int $replyId,
    ) {}

    public function toArray(object $notifiable): array
    {
        $ref = SupportTicket::formattedReference($this->supportTicketId);

        $message = sprintf(
            'REPRONIG support replied to ticket %s: "%s".',
            $ref,
            $this->subjectLine
        );

        return [
            ...$this->basePayload(
                'support_ticket_staff_reply',
                sprintf('Reply on ticket %s', $ref),
                $message,
                'info',
                SupportTicket::portalTicketDetailPath($this->supportInboxPath, $this->supportTicketId),
                [
                    'entity_type' => 'support_ticket',
                    'entity_id' => $this->supportTicketId,
                    'reply_id' => $this->replyId,
                ]
            ),
            'category' => 'support',
        ];
    }
}
