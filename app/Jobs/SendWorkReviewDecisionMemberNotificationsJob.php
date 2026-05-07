<?php

namespace App\Jobs;

use App\Mail\Works\WorkReviewDecisionMemberMailable;
use App\Models\Work;
use App\Notifications\System\WorkReviewDecisionMemberSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWorkReviewDecisionMemberNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $workId,
        public string $decision,
    ) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->workId < 1) {
            return;
        }

        $work = Work::query()
            ->with(['member.user'])
            ->find($this->workId);

        $memberUser = optional($work?->member)->user;
        if (! $work || ! $memberUser) {
            return;
        }

        $workTitle = (string) ($work->title ?? 'your work');
        $reviewNote = (string) ($work->review_reason ?? '');

        if ($memberUser->email) {
            $mailService->sendWorkReviewDecisionToMember(
                $memberUser,
                $work,
                $this->decision,
                new WorkReviewDecisionMemberMailable($work, $this->decision)
            );
        }

        $systemNotifications->send(
            $memberUser,
            new WorkReviewDecisionMemberSystemNotification($workTitle, $this->decision, $work->id, $reviewNote !== '' ? $reviewNote : null),
            'work_reviewed_member',
            WorkReviewDecisionMemberSystemNotification::subjectForDecision($this->decision)
        );
    }
}
