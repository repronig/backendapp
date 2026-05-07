<?php

namespace App\Jobs;

use App\Mail\Works\WorkSubmittedAdminMailable;
use App\Models\User;
use App\Models\Work;
use App\Notifications\System\WorkSubmittedAdminSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWorkSubmittedAdminNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $workId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->workId < 1) {
            return;
        }

        $work = Work::query()
            ->with(['member.user'])
            ->find($this->workId);

        if (! $work) {
            return;
        }

        $memberName = (string) (optional(optional($work->member)->user)->name ?? 'A member');
        $workTitle = (string) ($work->title ?? 'Untitled work');

        foreach (User::adminAlertRecipients() as $admin) {
            if ($admin->email) {
                $mailService->sendWorkSubmittedToAdmin($admin, $work, new WorkSubmittedAdminMailable($work));
            }

            $systemNotifications->send(
                $admin,
                new WorkSubmittedAdminSystemNotification($memberName, $workTitle, (int) $work->id),
                'work_submitted_admin',
                'New work submitted'
            );
        }
    }
}
