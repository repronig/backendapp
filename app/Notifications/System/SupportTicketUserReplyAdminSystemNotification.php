<?php

namespace App\Notifications\System;

use App\Models\SupportTicket;

class SupportTicketUserReplyAdminSystemNotification extends BaseSystemNotification
{
    public function __construct(
        public string $replierDisplayName,
        public int $supportTicketId,
        public string $subjectLine,
        public string $actionUrl,
        public int $replyId,
    ) {}

    public function toArray(object $notifiable): array
    {
        $ref = SupportTicket::formattedReference($this->supportTicketId);

        $message = sprintf(
            '%s replied on ticket %s: "%s".',
            $this->replierDisplayName,
            $ref,
            $this->subjectLine
        );

        return [
            ...$this->basePayload(
                'support_ticket_user_reply_admin',
                sprintf('Reply on ticket %s', $ref),
                $message,
                'info',
                SupportTicket::portalTicketDetailPath($this->actionUrl, $this->supportTicketId),
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
