<?php

namespace App\Notifications\System;

class DeclarationRejectedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected ?int $declarationId = null,
        protected ?string $reason = null,
        protected ?string $licensingYear = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = 'Your annual declaration was rejected.';

        if ($this->licensingYear) {
            $message .= ' Licensing year: ' . $this->licensingYear . '.';
        }

        if ($this->reason) {
            $message .= ' Reason: ' . $this->reason . '.';
        }

        return [
            ...$this->basePayload(
                'declaration_rejected',
                'Declaration rejected',
                $message,
                'warning',
                '/institution/declarations',
                [
                    'entity_type' => 'declaration',
                    'entity_id' => $this->declarationId,
                    'licensing_year' => $this->licensingYear,
                    'reason' => $this->reason,
                ]
            ),
            'category' => 'licensing',
        ];
    }
}
