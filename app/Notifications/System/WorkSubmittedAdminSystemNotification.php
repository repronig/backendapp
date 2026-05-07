<?php

namespace App\Notifications\System;

class WorkSubmittedAdminSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $memberName,
        protected string $workTitle,
        protected ?int $workId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf('%s submitted a new work: “%s”. Review it in the admin works queue.', $this->memberName, $this->workTitle);

        return [
            ...$this->basePayload(
                'work_submitted_admin',
                'New work submitted',
                $message,
                'info',
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
