<?php

namespace App\Notifications\System;

class PaymentReceivedAdminSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $institutionName,
        protected string $amountLabel,
        protected ?string $reference = null,
        protected ?int $paymentId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            '%s paid %s toward a licence invoice.',
            $this->institutionName,
            $this->amountLabel
        );

        if ($this->reference) {
            $message .= ' Reference: '.$this->reference.'.';
        }

        return [
            ...$this->basePayload(
                'payment_received_admin',
                'Institution payment received',
                $message,
                'info',
                '/admin/finance',
                [
                    'entity_type' => 'payment',
                    'entity_id' => $this->paymentId,
                    'payment_reference' => $this->reference,
                ]
            ),
            'category' => 'payment',
        ];
    }
}
