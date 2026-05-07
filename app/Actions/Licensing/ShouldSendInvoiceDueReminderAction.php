<?php

namespace App\Actions\Licensing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class ShouldSendInvoiceDueReminderAction
{
    public function execute(Invoice $invoice): bool
    {
        $invoice->refresh();

        if (! in_array($invoice->invoice_status, [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value], true)) {
            return false;
        }

        if (! $invoice->due_date || now()->startOfDay()->gt($invoice->due_date->startOfDay())) {
            return false;
        }

        if (! now()->startOfDay()->equalTo($invoice->due_date->copy()->startOfDay())) {
            return false;
        }

        return ! $invoice->last_due_reminder_sent_at || ! $invoice->last_due_reminder_sent_at->isToday();
    }
}
