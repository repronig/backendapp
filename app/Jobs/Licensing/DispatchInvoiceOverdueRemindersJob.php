<?php

namespace App\Jobs\Licensing;

use App\Actions\Licensing\MarkInvoiceOverdueAction;
use App\Actions\Licensing\ShouldSendInvoiceOverdueReminderAction;
use App\Enums\InvoiceStatus;
use App\Jobs\SendInvoiceOverdueReminderJob;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchInvoiceOverdueRemindersJob implements ShouldQueue
{
    use Queueable;

    public function handle(MarkInvoiceOverdueAction $markInvoiceOverdueAction, ShouldSendInvoiceOverdueReminderAction $shouldSendInvoiceOverdueReminderAction): void
    {
        Invoice::query()
            ->with('institution')
            ->whereIn('invoice_status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value, InvoiceStatus::Overdue->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->lazyById()
            ->each(function (Invoice $invoice) use ($markInvoiceOverdueAction, $shouldSendInvoiceOverdueReminderAction): void {
                $invoice = $markInvoiceOverdueAction->execute($invoice);

                if ($shouldSendInvoiceOverdueReminderAction->execute($invoice)) {
                    SendInvoiceOverdueReminderJob::dispatch($invoice->id);
                }
            });
    }
}
