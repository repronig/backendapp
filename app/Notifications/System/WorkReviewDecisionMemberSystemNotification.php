<?php

namespace App\Notifications\System;

class WorkReviewDecisionMemberSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $workTitle,
        protected string $decision,
        protected ?int $workId = null,
        protected ?string $reviewNote = null,
    ) {}

    public static function subjectForDecision(string $decision): string
    {
        return match ($decision) {
            'verified' => 'Work verified',
            'approved' => 'Work approved',
            'rejected' => 'Work rejected',
            'changes_requested' => 'Changes requested',
            default => 'Work review update',
        };
    }

    public function toArray(object $notifiable): array
    {
        $title = self::subjectForDecision($this->decision);

        $message = match ($this->decision) {
            'verified' => sprintf('Your work “%s” has been verified.', $this->workTitle),
            'approved' => sprintf('Your work “%s” has been approved.', $this->workTitle),
            'rejected' => sprintf('Your work “%s” was rejected.', $this->workTitle),
            'changes_requested' => sprintf('Changes were requested for your work “%s”.', $this->workTitle),
            default => sprintf('Your work “%s” has a new review update (%s).', $this->workTitle, $this->decision),
        };

        if ($this->reviewNote) {
            $message .= ' Note: '.$this->reviewNote;
        }

        $severity = in_array($this->decision, ['verified', 'approved'], true) ? 'success' : (in_array($this->decision, ['rejected'], true) ? 'warning' : 'info');

        return [
            ...$this->basePayload(
                'work_reviewed_member',
                $title,
                $message,
                $severity,
                '/member/works',
                [
                    'entity_type' => 'work',
                    'entity_id' => $this->workId,
                    'decision' => $this->decision,
                ]
            ),
            'category' => 'repertoire',
        ];
    }
}
