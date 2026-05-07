<?php

namespace App\Jobs;

use App\Actions\Licensing\SendInvoiceOverdueReminderAction;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInvoiceOverdueReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceId) {}

    public function handle(SendInvoiceOverdueReminderAction $sendInvoiceOverdueReminderAction): void
    {
        $invoice = Invoice::query()->with(['institution', 'declaration', 'licence'])->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $sendInvoiceOverdueReminderAction->execute($invoice);
    }
}
