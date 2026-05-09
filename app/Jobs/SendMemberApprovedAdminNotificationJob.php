<?php

namespace App\Jobs;

use App\Mail\Members\AdminMemberApprovedMailable;
use App\Mail\Members\MemberAffiliationAssociationDecisionMemberMailable;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\System\MemberAffiliationAssociationDecisionMemberNotification;
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

        $applicant = $this->memberApplication->user;
        if ($applicant !== null && filled($applicant->email)) {
            $memberSubject = $this->decision === 'rejected'
                ? 'Your membership affiliation was declined by your association'
                : 'Your membership affiliation was validated by your association';

            $mailService->sendMailable(
                $applicant->id,
                (string) $applicant->email,
                'member_affiliation_association_decision',
                $memberSubject,
                new MemberAffiliationAssociationDecisionMemberMailable($this->memberApplication, $this->decision),
                ['entity_type' => 'member_application', 'entity_id' => $this->memberApplication->id]
            );

            $systemNotifications->send(
                $applicant,
                new MemberAffiliationAssociationDecisionMemberNotification($this->memberApplication, $this->decision),
                'member_affiliation_association_decision',
                $memberSubject
            );
        }
    }
}
