<?php

namespace App\Listeners;

use App\Events\MemberApplicationApprovedByAssociation;
use App\Jobs\Notifications\SendMemberApprovalConfirmationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberApprovalConfirmation implements ShouldQueue
{
    use Queueable;

    public function handle(MemberApplicationApprovedByAssociation $event): void
    {
        SendMemberApprovalConfirmationJob::dispatch(
            $event->memberApplication,
            $event->member->member_code,
        );
    }
}
