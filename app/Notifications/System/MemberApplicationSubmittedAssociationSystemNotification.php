<?php

namespace App\Notifications\System;

class MemberApplicationSubmittedAssociationSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $applicantName,
        protected string $associationName,
        protected ?string $applicationReference = null,
        protected ?int $applicationId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            '%s submitted a member application for %s.',
            $this->applicantName,
            $this->associationName
        );

        if ($this->applicationReference) {
            $message .= ' Ref: '.$this->applicationReference.'.';
        }

        return [
            ...$this->basePayload(
                'member_application_submitted_association',
                'New member application submitted',
                $message,
                'info',
                '/admin/membership',
                [
                    'entity_type' => 'member_application',
                    'entity_id' => $this->applicationId,
                    'application_reference' => $this->applicationReference,
                ]
            ),
            'category' => 'application',
        ];
    }
}
