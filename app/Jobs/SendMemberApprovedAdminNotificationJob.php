<?php

namespace App\Jobs;

use App\Mail\Members\AdminMemberApprovedMailable;
use App\Models\MemberApplication;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberApprovedAdminNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public MemberApplication $memberApplication, public User $reviewer) {}

    public function handle(MailService $mailService): void
    {
        $recipients = config('mail.admin_notification_recipients', []);

        if (empty($recipients)) {
            $recipients = User::adminAlertRecipients()->pluck('email')->filter()->values()->all();
        }

        foreach ($recipients as $recipient) {
            $mailService->sendMailable(
                null,
                (string) $recipient,
                'admin_member_approved',
                'New Member Approved by Association',
                new AdminMemberApprovedMailable($this->memberApplication, $this->reviewer),
                ['entity_type' => 'member_application', 'entity_id' => $this->memberApplication->id]
            );
        }
    }
}
