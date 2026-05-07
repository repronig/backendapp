<?php

namespace App\Notifications\System;

class OfflineLicencePaymentRejectedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $paymentReference,
        protected string $amountFormatted,
        protected ?string $licenceId = null,
        protected ?string $reason = null,
        protected ?int $paymentId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = 'Your offline licence payment has been rejected.';

        if ($this->paymentReference) {
            $message .= ' Reference: '.$this->paymentReference.'.';
        }

        if ($this->reason) {
            $message .= ' Reason: '.$this->reason.'.';
        }

        if ($this->amountFormatted) {
            $message .= ' Amount: '.$this->amountFormatted.'.';
        }

        return [
            ...$this->basePayload(
                'offline_licence_payment_rejected',
                'Offline payment rejected',
                $message,
                'warning',
                '/institution/licences',
                [
                    'entity_type' => 'payment',
                    'entity_id' => $this->paymentId,
                    'payment_reference' => $this->paymentReference,
                    'licence_id' => $this->licenceId,
                ]
            ),
            'category' => 'payment',
        ];
    }
}
