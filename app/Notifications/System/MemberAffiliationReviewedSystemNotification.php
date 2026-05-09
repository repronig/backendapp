<?php

namespace App\Notifications\System;

use App\Models\MemberApplication;
use App\Models\User;

class MemberAffiliationReviewedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected MemberApplication $application,
        protected User $reviewer,
        protected string $decision = 'validated',
    ) {}

    public function toArray(object $notifiable): array
    {
        $isRejected = $this->decision === 'rejected';
        $title = $isRejected ? 'Member affiliation rejected' : 'Member affiliation validated';
        $message = sprintf(
            '%s %s the affiliation for %s. Admin final decision is required.',
            $this->reviewer->name ?? 'An association officer',
            $isRejected ? 'rejected' : 'validated',
            $this->application->user?->name ?? $this->application->application_reference ?? 'a member application'
        );

        return [
            ...$this->basePayload(
                'member_affiliation_reviewed',
                $title,
                $message,
                $isRejected ? 'warning' : 'info',
                '/admin/membership',
                [
                    'entity_type' => 'member_application',
                    'entity_id' => $this->application->id,
                    'application_reference' => $this->application->application_reference,
                    'decision' => $this->decision,
                ]
            ),
            'category' => 'application',
        ];
    }
}
