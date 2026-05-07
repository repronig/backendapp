<?php

namespace App\Notifications\System;

class OfflineInvoicePaymentSubmittedAdminSystemNotification extends BaseSystemNotification
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
            '%s submitted an offline payment of %s for admin review.',
            $this->institutionName,
            $this->amountLabel
        );

        if ($this->reference) {
            $message .= ' Reference: '.$this->reference.'.';
        }

        return [
            ...$this->basePayload(
                'offline_invoice_payment_submitted_admin',
                'Offline payment submitted for review',
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
