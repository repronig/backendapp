<?php

namespace App\Listeners;

use App\Events\InstitutionInvoiceGenerated;
use App\Jobs\SendInvoiceGeneratedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInvoiceGeneratedEmail implements ShouldQueue
{
    use Queueable;

    public function handle(InstitutionInvoiceGenerated $event): void
    {
        SendInvoiceGeneratedNotificationJob::dispatch($event->invoice);
    }
}
