<?php

namespace App\Listeners;

use App\Events\InstitutionApproved;
use App\Jobs\SendInstitutionApprovedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInstitutionApprovedEmail implements ShouldQueue
{
    use Queueable;

    public function handle(InstitutionApproved $event): void
    {
        SendInstitutionApprovedNotificationJob::dispatch($event->institution);
    }
}
