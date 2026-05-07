<?php

namespace App\Notifications\System;

class InvoiceDueReminderSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $invoiceNumber,
        protected string $dueDate,
        protected string $outstandingFormatted,
        protected ?int $invoiceId = null,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            'Invoice %s is due on %s. Outstanding balance: %s.',
            $this->invoiceNumber,
            $this->dueDate,
            $this->outstandingFormatted
        );

        return [
            ...$this->basePayload(
                'invoice_due_reminder',
                'Invoice due reminder',
                $message,
                'info',
                '/institution/invoices',
                [
                    'entity_type' => 'invoice',
                    'entity_id' => $this->invoiceId,
                    'invoice_number' => $this->invoiceNumber,
                    'due_date' => $this->dueDate,
                ]
            ),
            'category' => 'licensing',
        ];
    }
}
