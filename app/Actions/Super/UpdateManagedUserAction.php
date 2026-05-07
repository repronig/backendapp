<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateManagedUserAction
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
            $before = $user->load([
                'roles',
                'associations',
                'member.association',
                'institutionUsers.institution',
            ])->toArray();

            $update = Collection::make($data)
                ->except([
                    'roles',
                    'association_ids',
                    'institution_id',
                    'institution_role_label',
                    'institution_is_primary',
                    'institution_is_active',
                    'password',
                ])
                ->toArray();

            if (! empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            if (array_key_exists('account_type', $update) && in_array($update['account_type'], ['admin', 'super_admin'], true) && ! $user->admin_pin_hash) {
                $update['admin_pin_hash'] = Hash::make('123456');
            }

            if (! empty($update)) {
                $user->update($update);
            }

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles']);
            }

            if (array_key_exists('association_ids', $data)) {
                $user->associations()->sync($data['association_ids'] ?? []);
            }

            if (array_key_exists('institution_id', $data)) {
                $user->institutionUsers()->delete();

                if (! empty($data['institution_id'])) {
                    InstitutionUser::query()->create([
                        'institution_id' => $data['institution_id'],
                        'user_id' => $user->id,
                        'role_label' => $data['institution_role_label'] ?? 'primary_contact',
                        'is_primary' => $data['institution_is_primary'] ?? true,
                        'is_active' => $data['institution_is_active'] ?? true,
                    ]);
                }
            }

            $fresh = $user->fresh([
                'roles',
                'associations',
                'member.association',
                'institutionUsers.institution',
            ]);

            $this->logAuditAction->execute(
                $actor,
                'managed_user_updated',
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