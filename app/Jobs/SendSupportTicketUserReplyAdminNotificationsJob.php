<?php

namespace App\Jobs;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\System\SupportTicketUserReplyAdminSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSupportTicketUserReplyAdminNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $supportTicketReplyId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->supportTicketReplyId < 1) {
            return;
        }

        $reply = SupportTicketReply::query()
            ->with(['supportTicket.user', 'user'])
            ->find($this->supportTicketReplyId);

        if (! $reply || $reply->is_staff || ! $reply->supportTicket) {
            return;
        }

        $ticket = $reply->supportTicket;
        $subject = (string) $ticket->subject;
        $replierName = (string) ($reply->user?->name ?? $ticket->user?->name ?? 'Ticket owner');
        $base = rtrim((string) config('app.frontend_url'), '/');

        foreach (User::adminAlertRecipients() as $admin) {
            $actionPath = $admin->adminSupportTicketsInboxPath();
            $ticketUrl = $base.SupportTicket::portalTicketDetailPath($actionPath, (int) $ticket->id);

            if ($admin->email) {
                $mailService->sendSupportTicketUserReplyAdmin($admin, $ticket, $reply, $ticketUrl);
            }

            $systemNotifications->send(
                $admin,
                new SupportTicketUserReplyAdminSystemNotification(
                    $replierName,
                    (int) $ticket->id,
                    $subject,
                    $actionPath,
                    (int) $reply->id
                ),
                'support_ticket_user_reply_admin',
                'Support ticket reply',
                ['support_ticket_id' => $ticket->id, 'reply_id' => $reply->id, 'admin_id' => $admin->id]
            );
        }
    }
}
