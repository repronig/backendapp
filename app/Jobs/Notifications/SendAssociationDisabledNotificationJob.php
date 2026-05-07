<?php

namespace App\Jobs\Notifications;

use App\Models\Association;
use App\Notifications\System\AssociationDisabledSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAssociationDisabledNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Association $association) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $association = $this->association->fresh(['users']);

        if ($association?->contact_email) {
            $mailService->sendAssociationDisabled($association->contact_email, $association);
        }

        if (! $association) {
            return;
        }

        foreach ($association->users as $user) {
            if (! (bool) ($user->pivot?->is_active)) {
                continue;
            }

            $systemNotifications->send(
                $user,
                new AssociationDisabledSystemNotification($association->name, $association->disable_reason, $association->external_id),
                'association_disabled',
                'Association disabled'
            );
        }
    }
}
