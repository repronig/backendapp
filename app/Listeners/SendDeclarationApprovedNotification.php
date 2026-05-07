<?php

namespace App\Listeners;

use App\Events\InstitutionAnnualDeclarationApproved;
use App\Jobs\SendDeclarationApprovedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDeclarationApprovedNotification implements ShouldQueue
{
    use Queueable;

    public function handle(InstitutionAnnualDeclarationApproved $event): void
    {
        SendDeclarationApprovedNotificationJob::dispatch($event->declaration);
    }
}
