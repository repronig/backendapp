<?php

namespace App\Notifications\System;

class WorkUpdateRequestedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $workTitle,
        protected ?int $workId = null,
        protected ?string $memberName = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            '%s requested to edit approved work "%s".',
            $this->memberName ?: 'A member',
            $this->workTitle
        );

        return [
            ...$this->basePayload(
                'work_update_requested',
                'Work update request submitted',
                $message,
                'warning',
                '/admin/works',
                [
                    'entity_type' => 'work',
                    'entity_id' => $this->workId,
                ]
            ),
            'category' => 'repertoire',
        ];
    }
}
