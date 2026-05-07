<?php

namespace App\Notifications\System;

class MemberApplicationRejectedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?string $reason = null,
        protected ?string $applicationExternalId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = 'Your REPRONIG membership application was not approved.';

        if ($this->reason) {
            $message .= ' Reason: '.$this->reason.'.';
        }

        return [
            ...$this->basePayload(
                'member_application_rejected',
                'Member application rejected',
                $message,
                'warning',
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
