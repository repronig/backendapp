<?php

namespace App\Jobs\Licensing;

use App\Actions\Licensing\ShouldSendInvoiceDueReminderAction;
use App\Enums\InvoiceStatus;
use App\Jobs\SendInvoiceDueReminderJob;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchInvoiceDueRemindersJob implements ShouldQueue
{
    use Queueable;

    public function handle(ShouldSendInvoiceDueReminderAction $shouldSendInvoiceDueReminderAction): void
    {
        Invoice::query()
            ->with('institution')
            ->whereIn('invoice_status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->whereDate('due_date', now()->toDateString())
            ->lazyById()
            ->each(function (Invoice $invoice) use ($shouldSendInvoiceDueReminderAction): void {
                if ($shouldSendInvoiceDueReminderAction->execute($invoice)) {
                    SendInvoiceDueReminderJob::dispatch($invoice->id);
                }
            });
    }
}
