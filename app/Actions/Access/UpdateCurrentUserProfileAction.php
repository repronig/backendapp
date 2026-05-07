<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Notifications\System\SecurityEventSystemNotification;
use App\Services\Notifications\SystemNotificationService;

class UpdateCurrentUserProfileAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected SystemNotificationService $systemNotifications
    ) {
    }

    public function execute(
        User $user,
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): User {
        $before = $user->toArray();

        $user->update($data);

        $fresh = $user->fresh([
            'roles',
            'associations',
            'member.association',
            'institutionUsers.institution',
        ]);

        $this->logAuditAction->execute(
            $user,
            'current_user_profile_updated',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        $this->systemNotifications->send(
            $fresh,
            new SecurityEventSystemNotification(
                'profile_updated',
                'Profile updated',
                'Your account profile details were updated successfully.',
                $this->settingsUrlFor($fresh)
            ),
            'security_profile_updated'
        );

        return $fresh;
    }

    protected function settingsUrlFor(User $user): string
    {
        if ($user->hasRole('super_admin')) {
            return '/super-admin/account-settings';
        }

        if ($user->hasRole('admin')) {
            return '/admin/settings';
        }

        if ($user->institutionUsers()->exists()) {
            return '/institution/settings';
        }

        if ($user->associations()->exists()) {
            return '/association/settings';
        }

        return '/member/settings';
    }
}
