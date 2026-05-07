<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class InstitutionPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, Institution $institution): bool
    {
        return $user->hasRole('institution_user')
            && $user->institutionUsers()
                ->where('institution_users.institution_id', $institution->id)
                ->where('institution_users.is_active', true)
                ->exists()
            && in_array($institution->account_status, ['pending_review', 'active'], true)
            && in_array($institution->governance_status, [null, 'normal', 'restricted'], true);
    }

    public function update(User $user, Institution $institution): bool
    {
        return $this->view($user, $institution)
            && in_array($institution->onboarding_status, ['draft', 'submitted', 'under_review', 'approved'], true);
    }

    public function uploadDocument(User $user, Institution $institution): bool
    {
        return $this->update($user, $institution)
            && $institution->account_status !== 'active';
    }

    public function createDeclaration(User $user, Institution $institution): bool
    {
        return $user->hasRole('institution_user')
            && $user->institutionUsers()
                ->where('institution_users.institution_id', $institution->id)
                ->where('institution_users.is_active', true)
                ->exists()
            && $institution->account_status === 'active'
            && in_array($institution->governance_status, [null, 'normal', 'restricted'], true);
    }

    public function approve(User $user, Institution $institution): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Record first-login acceptance of published institution licensing terms.
     */
    public function acceptLicensingTerms(User $user, Institution $institution): bool
    {
        return $this->view($user, $institution);
    }
}
