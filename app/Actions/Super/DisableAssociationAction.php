<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Events\AssociationDisabled;
use App\Models\Association;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DisableAssociationAction
{
    public function __construct(protected LogAuditAction $logAuditAction) {}

    public function execute(Association $association, User $actor, ?string $reason = null, ?string $ipAddress = null, ?string $userAgent = null): Association
    {
        return DB::transaction(function () use ($association, $actor, $reason, $ipAddress, $userAgent) {
            $before = $association->toArray();

            $association->update([
                'status' => 'inactive',
                'is_enabled' => false,
                'disabled_at' => now(),
                'disabled_by_user_id' => $actor->id,
                'disable_reason' => $reason,
            ]);

            $this->logAuditAction->execute($actor, 'association_disabled', $association->fresh(), $before, $association->fresh()->toArray(), $ipAddress, $userAgent);
            event(new AssociationDisabled($association->fresh(), $actor));

            return $association->fresh(['state', 'city']);
        });
    }
}
