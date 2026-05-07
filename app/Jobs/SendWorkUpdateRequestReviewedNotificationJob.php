<?php

namespace App\Jobs;

use App\Mail\Works\WorkUpdateRequestReviewedMailable;
use App\Models\Work;
use App\Notifications\System\WorkUpdateRequestReviewedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWorkUpdateRequestReviewedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Work $work,
        public string $decision
    ) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $work = $this->work->fresh(['member.user']);
        $memberUser = optional($work?->member)->user;
        if (! $work || ! $memberUser) {
            return;
        }

        if ($memberUser->email) {
            $mailService->sendWorkUpdateReviewed(
                (string) $memberUser->email,
                $work->id,
                $this->decision,
                new WorkUpdateRequestReviewedMailable($work, $this->decision)
            );
        }

        $systemNotifications->send(
            $memberUser,
            new WorkUpdateRequestReviewedSystemNotification((string) $work->title, $this->decision, $work->id),
            'work_update_request_reviewed',
            $this->decision === 'approved' ? 'Work update request approved' : 'Work update request rejected'
        );
    }
}
