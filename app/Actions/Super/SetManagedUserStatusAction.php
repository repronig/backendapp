<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;

class SetManagedUserStatusAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        User $user,
        string $status,
        User $actor,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): User {
        $before = $user->load([
            'roles',
            'associations',
            'member.association',
            'institutionUsers.institution',
        ])->toArray();

        $user->update([
            'status' => $status,
        ]);

        $fresh = $user->fresh([
            'roles',
            'associations',
            'member.association',
            'institutionUsers.institution',
        ]);

        $this->logAuditAction->execute(
            $actor,
            $status === 'active' ? 'managed_user_activated' : 'managed_user_status_changed',
            $fresh,
            $before,
            array_merge($fresh->toArray(), $reason ? ['status_change_reason' => $reason] : []),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}