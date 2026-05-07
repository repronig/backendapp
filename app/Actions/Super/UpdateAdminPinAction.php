<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateAdminPinAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        string $adminPin,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use ($adminPin, $actor, $ipAddress, $userAgent) {
            $adminUsers = User::adminAlertRecipients();
            $adminPinHash = Hash::make($adminPin);

            foreach ($adminUsers as $adminUser) {
                $adminUser->forceFill(['admin_pin_hash' => $adminPinHash])->save();
            }

            $freshActor = $actor->fresh();

            $this->logAuditAction->execute(
                $freshActor,
                'admin_pin_updated',
                $freshActor,
                null,
                [
                    'affected_admin_users_count' => $adminUsers->count(),
                    'updated_by_user_id' => $freshActor->id,
                ],
                $ipAddress,
                $userAgent
            );

            return [
                'configured' => true,
                'affected_admin_users_count' => $adminUsers->count(),
            ];
        });
    }
}
