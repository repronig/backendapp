<?php

namespace App\Jobs;

use App\Actions\Licensing\SendInvoiceDueReminderAction;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInvoiceDueReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceId) {}

    public function handle(SendInvoiceDueReminderAction $sendInvoiceDueReminderAction): void
    {
        $invoice = Invoice::query()->with(['institution', 'declaration', 'licence'])->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $sendInvoiceDueReminderAction->execute($invoice);
    }
}
