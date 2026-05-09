<?php

namespace App\Notifications\System;

use App\Models\MemberApplication;

class MemberAffiliationAssociationDecisionMemberNotification extends BaseSystemNotification
{
    public function __construct(
        protected MemberApplication $application,
        protected string $decision = 'validated',
    ) {}

    public function toArray(object $notifiable): array
    {
        $isRejected = $this->decision === 'rejected';
        $associationName = $this->application->association?->name ?? 'Your association';

        if ($isRejected) {
            $title = 'Affiliation declined';
            $message = sprintf(
                '%s has declined your membership affiliation. Open My Mandate to review the association note.',
                $associationName
            );
            $severity = 'warning';
        } else {
            $title = 'Affiliation validated';
            $message = sprintf(
                '%s has validated your membership affiliation. REPRONIG admin will review your application next.',
                $associationName
            );
            $severity = 'info';
        }

        return [
            ...$this->basePayload(
                'member_affiliation_association_decision',
                $title,
                $message,
                $severity,
                '/member/onboarding',
                [
                    'entity_type' => 'member_application',
                    'entity_id' => $this->application->id,
                    'application_reference' => $this->application->application_reference,
                    'decision' => $this->decision,
                    'association_id' => $this->application->association_id,
                ]
            ),
            'category' => 'application',
        ];
    }
}
