<?php

namespace App\Jobs;

use App\Enums\SupportTicketPortalContext;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\System\SupportTicketSubmittedAdminSystemNotification;
use App\Notifications\System\SupportTicketSubmittedUserSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSupportTicketSubmittedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $supportTicketId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->supportTicketId < 1) {
            return;
        }

        $ticket = SupportTicket::query()->with('user')->find($this->supportTicketId);
        if (! $ticket || ! $ticket->user) {
            return;
        }

        $owner = $ticket->user;
        $portalContext = $ticket->portal_context instanceof SupportTicketPortalContext
            ? $ticket->portal_context
            : SupportTicketPortalContext::Member;
        $supportInboxPath = $portalContext->frontendSupportPath();
        $subject = (string) $ticket->subject;

        if ($owner->email) {
            $mailService->sendSupportTicketSubmittedUser($owner, $ticket, $supportInboxPath);
        }

        $systemNotifications->send(
            $owner,
            new SupportTicketSubmittedUserSystemNotification((int) $ticket->id, $subject, $supportInboxPath),
            'support_ticket_submitted_user',
            'Support request received',
            ['support_ticket_id' => $ticket->id]
        );

        $base = rtrim((string) config('app.frontend_url'), '/');

        foreach (User::adminAlertRecipients() as $admin) {
            $actionPath = $admin->adminSupportTicketsInboxPath();
            $ticketUrl = $base.SupportTicket::portalTicketDetailPath($actionPath, (int) $ticket->id);

            if ($admin->email) {
                $mailService->sendSupportTicketSubmittedAdmin($admin, $ticket, $ticketUrl);
            }

            $systemNotifications->send(
                $admin,
                new SupportTicketSubmittedAdminSystemNotification(
                    (string) ($owner->name ?? 'A user'),
                    (int) $ticket->id,
                    $subject,
                    $actionPath
                ),
                'support_ticket_submitted_admin',
                'New support ticket',
                ['support_ticket_id' => $ticket->id, 'admin_id' => $admin->id]
            );
        }
    }
}
