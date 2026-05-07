<?php

namespace App\Policies;

use App\Models\UsageDeclaration;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class UsageDeclarationPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, UsageDeclaration $usageDeclaration): bool
    {
        return $user->hasRole('institution_user')
            && $user->institutionUsers()
                ->join('institutions', 'institutions.id', '=', 'institution_users.institution_id')
                ->where('institution_users.institution_id', $usageDeclaration->institution_id)
                ->where('institution_users.is_active', true)
                ->where('institutions.account_status', 'active')
                ->where(function ($query) {
                    $query->whereNull('institutions.governance_status')
                        ->orWhereIn('institutions.governance_status', ['normal', 'restricted']);
                })
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('institution_user')
            && $user->institutionUsers()
                ->join('institutions', 'institutions.id', '=', 'institution_users.institution_id')
                ->where('institution_users.is_active', true)
                ->where('institutions.account_status', 'active')
                ->where(function ($query) {
                    $query->whereNull('institutions.governance_status')
                        ->orWhereIn('institutions.governance_status', ['normal', 'restricted']);
                })
                ->exists();
    }
}