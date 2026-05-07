<?php

namespace App\Notifications\System;

class InvoiceOverdueReminderSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $invoiceNumber,
        protected string $outstandingFormatted,
        protected ?int $invoiceId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            'Invoice %s is overdue. Outstanding balance: %s.',
            $this->invoiceNumber,
            $this->outstandingFormatted
        );

        return [
            ...$this->basePayload(
                'invoice_overdue_reminder',
                'Invoice overdue',
                $message,
                'warning',
                '/institution/invoices',
                [
                    'entity_type' => 'invoice',
                    'entity_id' => $this->invoiceId,
                    'invoice_number' => $this->invoiceNumber,
                ]
            ),
            'category' => 'licensing',
        ];
    }
}
