<?php

namespace App\Jobs;

use App\Models\Institution;
use App\Notifications\System\InstitutionApprovedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInstitutionApprovedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Institution $institution) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $institution = $this->institution->fresh(['institutionUsers.user']);

        $mailService->sendInstitutionApproved($institution);

        foreach ($institution->institutionUsers as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $systemNotifications->send(
                $institutionUser->user,
                new InstitutionApprovedSystemNotification($institution->external_id, $institution->licence_id),
                'institution_approved',
                'Institution approved'
            );
        }
    }
}
