<?php

namespace App\Listeners;

use App\Actions\Licensing\SyncInvoiceFromPaymentAction;
use App\Events\LicencePaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateInvoiceBalances implements ShouldQueue
{
    use Queueable;

    public function __construct(protected SyncInvoiceFromPaymentAction $syncInvoiceFromPaymentAction) {}

    public function handle(LicencePaymentReceived $event): void
    {
        $this->syncInvoiceFromPaymentAction->execute($event->payment);
    }
}
