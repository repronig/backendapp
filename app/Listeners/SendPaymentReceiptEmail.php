<?php

namespace App\Listeners;

use App\Events\LicencePaymentReceived;
use App\Jobs\SendPaymentReceivedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentReceiptEmail implements ShouldQueueAfterCommit
{
    use Queueable;

    public function handle(LicencePaymentReceived $event): void
    {
        SendPaymentReceivedNotificationJob::dispatch((int) $event->payment->getKey())->afterCommit();
    }
}
