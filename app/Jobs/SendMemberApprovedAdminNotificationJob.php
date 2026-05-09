<?php

namespace App\Jobs;

use App\Mail\Members\AdminMemberApprovedMailable;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\System\MemberAffiliationReviewedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberApprovedAdminNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MemberApplication $memberApplication,
        public User $reviewer,
        public string $decision = 'validated'
    ) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $recipients = config('mail.admin_notification_recipients', []);

        if (empty($recipients)) {
            $recipients = User::adminAlertRecipients()->pluck('email')->filter()->values()->all();
        }

        $subject = $this->decision === 'rejected'
            ? 'Member Affiliation Rejected by Association'
            : 'Member Affiliation Validated by Association';

        foreach ($recipients as $recipient) {
            $mailService->sendMailable(
                null,
                (string) $recipient,
                'admin_member_affiliation_reviewed',
                $subject,
                new AdminMemberApprovedMailable($this->memberApplication, $this->reviewer, $this->decision),
                ['entity_type' => 'member_application', 'entity_id' => $this->memberApplication->id]
            );
        }

        foreach (User::adminAlertRecipients() as $admin) {
            $systemNotifications->send(
                $admin,
                new MemberAffiliationReviewedSystemNotification($this->memberApplication, $this->reviewer, $this->decision),
                'member_affiliation_reviewed',
                $subject
            );
        }
    }
}
