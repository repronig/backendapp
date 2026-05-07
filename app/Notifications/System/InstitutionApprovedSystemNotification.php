<?php

namespace App\Notifications\System;

class InstitutionApprovedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?string $institutionExternalId = null,
        protected ?string $licenceId = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = 'Your institution has been approved on REPRONIG.';

        if ($this->licenceId) {
            $message .= ' Licence ID: ' . $this->licenceId . '.';
        }

        return [
            ...$this->basePayload(
            'institution_approved',
            'Institution approved',
            $message,
            'success',
            '/institution/profile',
            [
                'entity_type' => 'institution',
                'entity_external_id' => $this->institutionExternalId,
                'licence_id' => $this->licenceId,
            ]
                    ),
            'category' => 'approval',
        ];
    }
}

