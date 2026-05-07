<?php

namespace App\Notifications\System;

class PaymentReceivedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $amount,
        protected ?string $reference = null,
        protected ?int $paymentId = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = 'A payment of ' . $this->amount . ' has been received.';

        if ($this->reference) {
            $message .= ' Reference: ' . $this->reference . '.';
        }

        return [
            ...$this->basePayload(
            'payment_received',
            'Payment received',
            $message,
            'success',
            '/institution/licences',
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

