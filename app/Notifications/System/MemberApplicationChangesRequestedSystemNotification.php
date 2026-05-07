<?php

namespace App\Notifications\System;

class MemberApplicationChangesRequestedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?string $comment = null,
        protected ?string $applicationExternalId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = 'Changes were requested on your REPRONIG membership application.';

        if ($this->comment) {
            $message .= ' Note: '.$this->comment.'.';
        }

        return [
            ...$this->basePayload(
                'member_application_changes_requested',
                'Changes requested',
                $message,
                'info',
                '/member/onboarding',
                [
                    'entity_type' => 'member_application',
                    'entity_external_id' => $this->applicationExternalId,
                ]
            ),
            'category' => 'approval',
        ];
    }
}
