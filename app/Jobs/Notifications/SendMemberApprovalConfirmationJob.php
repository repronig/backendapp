<?php

namespace App\Jobs\Notifications;

use App\Models\MemberApplication;
use App\Notifications\System\MemberApplicationApprovedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberApprovalConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public MemberApplication $memberApplication, public ?string $memberCode = null) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $application = $this->memberApplication->fresh(['user']);

        if (! $application?->user) {
            return;
        }

        $mailService->sendMemberApplicationApproved($application->user, $this->memberCode);
        $mailService->sendMemberWelcome($application->user);

        $systemNotifications->send(
            $application->user,
            new MemberApplicationApprovedSystemNotification($this->memberCode, $application->external_id),
            'member_application_approved',
            'Member application approved'
        );
    }
}
