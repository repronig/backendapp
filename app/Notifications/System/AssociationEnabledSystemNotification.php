<?php

namespace App\Notifications\System;

class AssociationEnabledSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $associationName,
        protected ?string $associationExternalId = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        return [
            ...$this->basePayload(
                'association_enabled',
                'Association reactivated',
                'Your association access for ' . $this->associationName . ' has been restored.',
                'success',
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
