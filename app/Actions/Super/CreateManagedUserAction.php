<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateManagedUserAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): User {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent) {
            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'admin_pin_hash' => in_array($data['account_type'], ['admin', 'super_admin'], true) ? Hash::make('123456') : null,
                'account_type' => $data['account_type'],
                'status' => $data['status'] ?? 'active',
                'email_verified_at' => now(),
            ]);

            $user->syncRoles($data['roles']);

            if (! empty($data['association_ids'])) {
                $user->associations()->sync($data['association_ids']);
            }

            if (! empty($data['institution_id'])) {
                InstitutionUser::query()->updateOrCreate(
                    [
                        'institution_id' => $data['institution_id'],
                        'user_id' => $user->id,
                    ],
                    [
                        'role_label' => $data['institution_role_label'] ?? 'primary_contact',
                        'is_primary' => $data['institution_is_primary'] ?? true,
                        'is_active' => $data['institution_is_active'] ?? true,
                    ]
                );
            }

            $fresh = $user->fresh([
                'roles',
                'associations',
                'member.association',
                'institutionUsers.institution',
            ]);

            $this->logAuditAction->execute(
                $actor,
                'managed_user_created',
                $fresh,
                null,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}