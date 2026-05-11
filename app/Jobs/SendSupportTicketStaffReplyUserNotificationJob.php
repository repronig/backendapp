<?php

namespace App\Jobs;

use App\Enums\SupportTicketPortalContext;
use App\Models\SupportTicketReply;
use App\Notifications\System\SupportTicketStaffReplyUserSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSupportTicketStaffReplyUserNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $supportTicketReplyId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->supportTicketReplyId < 1) {
            return;
        }

        $reply = SupportTicketReply::query()
            ->with(['supportTicket.user'])
            ->find($this->supportTicketReplyId);

        if (! $reply || ! $reply->is_staff || ! $reply->supportTicket?->user) {
            return;
        }

        $ticket = $reply->supportTicket;
        $owner = $ticket->user;
        $portalContext = $ticket->portal_context instanceof SupportTicketPortalContext
            ? $ticket->portal_context
            : SupportTicketPortalContext::Member;
        $supportInboxPath = $portalContext->frontendSupportPath();
        $subject = (string) $ticket->subject;

        if ($owner->email) {
            $mailService->sendSupportTicketStaffReplyUser($owner, $ticket, $reply, $supportInboxPath);
        }

        $systemNotifications->send(
            $owner,
            new SupportTicketStaffReplyUserSystemNotification(
                (int) $ticket->id,
                $subject,
                $supportInboxPath,
                (int) $reply->id
            ),
            'support_ticket_staff_reply',
            'New reply on your support ticket',
            ['support_ticket_id' => $ticket->id, 'reply_id' => $reply->id]
        );
    }
}
