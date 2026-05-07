<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\Association;
use App\Models\User;
use App\Notifications\System\AssociationEnabledSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\DB;

class EnableAssociationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {}

    public function execute(Association $association, User $actor, ?string $ipAddress = null, ?string $userAgent = null): Association
    {
        return DB::transaction(function () use ($association, $actor, $ipAddress, $userAgent) {
            $before = $association->toArray();

            $association->update([
                'status' => 'active',
                'is_enabled' => true,
                'disabled_at' => null,
                'disabled_by_user_id' => null,
                'disable_reason' => null,
            ]);

            $fresh = $association->fresh(['state', 'city', 'users']);

            $this->logAuditAction->execute($actor, 'association_enabled', $fresh, $before, $fresh->toArray(), $ipAddress, $userAgent);

            foreach ($fresh->users as $user) {
                if (! $user->pivot?->is_active) {
                    continue;
                }

                $this->systemNotifications->send(
                    $user,
                    new AssociationEnabledSystemNotification($fresh->name, $fresh->external_id),
                    'association_enabled',
                    'Association reactivated'
                );
            }

            $this->mailService->sendAssociationEnabled($fresh);

            return $fresh;
        });
    }
}
