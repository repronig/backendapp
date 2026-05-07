<?php

namespace App\Listeners;

use App\Events\MemberApplicationApprovedByAssociation;
use App\Jobs\SendMemberApprovedAdminNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyAdminsOfMemberApproval implements ShouldQueue
{
    use Queueable;

    public function handle(MemberApplicationApprovedByAssociation $event): void
    {
        SendMemberApprovedAdminNotificationJob::dispatch($event->memberApplication, $event->reviewer);
    }
}
