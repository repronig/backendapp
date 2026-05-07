<?php

namespace App\Listeners;

use App\Events\AssociationDisabled;
use App\Jobs\Notifications\SendAssociationDisabledNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAssociationDisabledNotification implements ShouldQueue
{
    use Queueable;

    public function handle(AssociationDisabled $event): void
    {
        SendAssociationDisabledNotificationJob::dispatch($event->association);
    }
}
