<?php

namespace App\Notifications\System;

class DeclarationApprovedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?int $declarationId = null,
        protected ?string $licenceId = null,
        protected ?string $licensingYear = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = 'Your annual declaration has been approved.';

        if ($this->licensingYear) {
            $message .= ' Licensing year: ' . $this->licensingYear . '.';
        }

        if ($this->licenceId) {
            $message .= ' Licence ID: ' . $this->licenceId . '.';
        }

        return [
            ...$this->basePayload(
                'declaration_approved',
                'Declaration approved',
                $message,
                'success',
                '/institution/declarations',
                [
                    'entity_type' => 'declaration',
                    'entity_id' => $this->declarationId,
                    'licence_id' => $this->licenceId,
                    'licensing_year' => $this->licensingYear,
                ]
            ),
            'category' => 'licensing',
        ];
    }
}
