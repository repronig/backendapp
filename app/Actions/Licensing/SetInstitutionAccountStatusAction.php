<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetInstitutionAccountStatusAction
{
    public function __construct(protected LogAuditAction $logAuditAction) {}

    public function execute(Institution $institution, string $status, User $actor, ?string $reason = null, ?string $ipAddress = null, ?string $userAgent = null): Institution
    {
        return DB::transaction(function () use ($institution, $status, $actor, $reason, $ipAddress, $userAgent) {
            $before = $institution->toArray();

            $isActive = $status === 'active';

            $institution->update([
                'account_status' => $status,
                'governance_status' => $isActive ? 'normal' : 'restricted',
                'governance_reason_code' => $isActive ? null : 'manual_deactivation',
                'governance_reason' => $isActive ? null : $reason,
                'governance_changed_by_user_id' => $actor->id,
                'governance_changed_at' => now(),
            ]);

            $fresh = $institution->fresh(['profile', 'state', 'city']);
            $this->logAuditAction->execute(
                $actor,
                $isActive ? 'institution_reactivated' : 'institution_deactivated',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
