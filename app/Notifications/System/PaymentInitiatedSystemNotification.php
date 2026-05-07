<?php

namespace App\Notifications\System;

class PaymentInitiatedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $amount,
        protected ?string $reference = null,
        protected ?int $paymentId = null,
        protected ?string $licenceId = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = 'A payment has been initiated for ' . $this->amount . '.';

        if ($this->reference) {
            $message .= ' Reference: ' . $this->reference . '.';
        }

        if ($this->licenceId) {
            $message .= ' Licence ID: ' . $this->licenceId . '.';
        }

        return [
            ...$this->basePayload(
                'payment_initiated',
                'Payment initiated',
                $message,
                'info',
                '/institution/licences',
                [
                    'entity_type' => 'payment',
                    'entity_id' => $this->paymentId,
                    'payment_reference' => $this->reference,
                    'licence_id' => $this->licenceId,
                ]
            ),
            'category' => 'payment',
        ];
    }
}
