<?php

namespace App\Notifications\System;

class InvoiceGeneratedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $invoiceNumber,
        protected string $totalAmount,
        protected ?string $dueDate = null,
        protected ?int $invoiceId = null,
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = sprintf('A new invoice (%s) has been generated for %s.', $this->invoiceNumber, $this->totalAmount);

        if ($this->dueDate) {
            $message .= ' Due date: ' . $this->dueDate . '.';
        }

        return [
            ...$this->basePayload(
            'invoice_generated',
            'Invoice generated',
            $message,
            'info',
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

