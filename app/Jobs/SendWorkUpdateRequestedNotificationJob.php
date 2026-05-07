<?php

namespace App\Jobs;

use App\Mail\Works\WorkUpdateRequestedMailable;
use App\Models\User;
use App\Models\Work;
use App\Notifications\System\WorkUpdateRequestedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWorkUpdateRequestedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Work $work) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $work = $this->work->fresh(['member.user']);
        if (! $work) {
            return;
        }

        $admins = User::adminAlertRecipients();
        foreach ($admins as $admin) {
            if ($admin->email) {
                $mailService->sendWorkUpdateRequested((string) $admin->email, $work->id, new WorkUpdateRequestedMailable($work));
            }

            $systemNotifications->send(
                $admin,
                new WorkUpdateRequestedSystemNotification(
                    (string) $work->title,
                    $work->id,
                    optional(optional($work->member)->user)->name
                ),
                'work_update_requested',
                'Work update request submitted'
            );
        }
    }
}
