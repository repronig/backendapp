<?php

namespace App\Notifications\System;

class WorkUpdateRequestReviewedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $workTitle,
        protected string $decision,
        protected ?int $workId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $approved = $this->decision === 'approved';
        $message = $approved
            ? sprintf('Your update request for "%s" was approved. You can edit and resubmit the work.', $this->workTitle)
            : sprintf('Your update request for "%s" was rejected.', $this->workTitle);

        return [
            ...$this->basePayload(
                'work_update_request_reviewed',
                $approved ? 'Work update request approved' : 'Work update request rejected',
                $message,
                $approved ? 'success' : 'warning',
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
