<?php

namespace App\Actions\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateAssociationOfficerCredentialsAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        User $user,
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): User {
        return DB::transaction(function () use ($user, $data, $actor, $ipAddress, $userAgent) {
            $before = $user->only(['id', 'email']);

            $update = [];

            if (array_key_exists('email', $data)) {
                $update['email'] = $data['email'];
            }

            if (! empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            if ($update !== []) {
                $user->update($update);
            }

            $fresh = $user->fresh(['roles', 'associations']);

            $this->logAuditAction->execute(
                $actor,
                'association_officer_credentials_updated',
                $fresh,
                $before,
                $fresh->only(['id', 'email']),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
