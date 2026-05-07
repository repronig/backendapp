<?php

namespace App\Jobs;

use App\Actions\Licensing\SyncInvoiceStateAction;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncInvoiceStatusJob implements ShouldQueue
{
    use Queueable;

    public function handle(SyncInvoiceStateAction $syncInvoiceStateAction): void
    {
        Invoice::query()->lazyById()->each(function (Invoice $invoice) use ($syncInvoiceStateAction): void {
            $syncInvoiceStateAction->execute($invoice);
        });
    }
}
