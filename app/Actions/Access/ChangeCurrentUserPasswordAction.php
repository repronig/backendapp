<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Notifications\System\SecurityEventSystemNotification;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangeCurrentUserPasswordAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected SystemNotificationService $systemNotifications
    ) {
    }

    public function execute(
        User $user,
        string $currentPassword,
        string $newPassword,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $before = $user->toArray();

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $fresh = $user->fresh();

        $this->logAuditAction->execute(
            $user,
            'current_user_password_changed',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        $this->systemNotifications->send(
            $fresh,
            new SecurityEventSystemNotification(
                'password_changed',
                'Password changed',
                'Your account password was changed successfully.',
                $this->settingsUrlFor($fresh)
            ),
            'security_password_changed'
        );
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
