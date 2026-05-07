<?php

namespace App\Notifications\System;

class MemberApplicationApprovedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?string $memberCode = null,
        protected ?string $applicationExternalId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = 'Your REPRONIG membership application has been approved.';

        if ($this->memberCode) {
            $message .= ' Your member code is '.$this->memberCode.'.';
        }

        return [
            ...$this->basePayload(
                'member_application_approved',
                'Member application approved',
                $message,
                'success',
                '/member/onboarding',
                [
                    'entity_type' => 'member_application',
                    'entity_external_id' => $this->applicationExternalId,
                    'member_code' => $this->memberCode,
                ]
            ),
            'category' => 'approval',
        ];
    }
}
