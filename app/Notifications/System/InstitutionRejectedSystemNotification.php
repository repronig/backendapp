<?php

namespace App\Notifications\System;

class InstitutionRejectedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?string $institutionExternalId = null,
        protected ?string $reason = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = 'Your institution registration has been rejected on REPRONIG.';
        if ($this->reason) {
            $message .= ' Reason: '.$this->reason;
        }

        return [
            ...$this->basePayload(
                'institution_rejected',
                'Institution registration rejected',
                $message,
                'warning',
                '/institution/profile',
                [
                    'entity_type' => 'institution',
                    'entity_external_id' => $this->institutionExternalId,
                    'reason' => $this->reason,
                ]
            ),
            'category' => 'approval',
        ];
    }
}
