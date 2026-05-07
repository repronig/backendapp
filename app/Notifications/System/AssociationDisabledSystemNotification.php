<?php

namespace App\Notifications\System;

class AssociationDisabledSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $associationName,
        protected ?string $reason = null,
        protected ?string $associationExternalId = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = 'Your association access for ' . $this->associationName . ' has been disabled.';

        if ($this->reason) {
            $message .= ' Reason: ' . $this->reason . '.';
        }

        return [
            ...$this->basePayload(
                'association_disabled',
                'Association disabled',
                $message,
                'warning',
                '/association/profile',
                [
                    'entity_type' => 'association',
                    'entity_external_id' => $this->associationExternalId,
                ]
            ),
            'category' => 'governance',
        ];
    }
}
