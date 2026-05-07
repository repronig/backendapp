<?php

namespace App\Actions\Licensing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class ShouldSendInvoiceOverdueReminderAction
{
    public function execute(Invoice $invoice): bool
    {
        $invoice->refresh();

        if ($invoice->invoice_status !== InvoiceStatus::Overdue->value) {
            return false;
        }

        if (! $invoice->due_date || ! now()->startOfDay()->gt($invoice->due_date->startOfDay())) {
            return false;
        }

        return ! $invoice->last_overdue_reminder_sent_at || ! $invoice->last_overdue_reminder_sent_at->isToday();
    }
}
