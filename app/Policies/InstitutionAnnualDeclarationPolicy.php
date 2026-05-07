<?php

namespace App\Policies;

use App\Enums\DeclarationStatus;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class InstitutionAnnualDeclarationPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, InstitutionAnnualDeclaration $declaration): bool
    {
        return $user->hasRole('institution_user')
            && $user->institutionUsers()
                ->join('institutions', 'institutions.id', '=', 'institution_users.institution_id')
                ->where('institution_users.institution_id', $declaration->institution_id)
                ->where('institution_users.is_active', true)
                ->where('institutions.account_status', 'active')
                ->where(function ($query) {
                    $query->whereNull('institutions.governance_status')
                        ->orWhereIn('institutions.governance_status', ['normal', 'restricted']);
                })
                ->exists();
    }

    public function update(User $user, InstitutionAnnualDeclaration $declaration): bool
    {
        return $this->view($user, $declaration)
            && $declaration->declaration_status === DeclarationStatus::Draft->value;
    }

    public function submit(User $user, InstitutionAnnualDeclaration $declaration): bool
    {
        return $this->view($user, $declaration)
            && $declaration->declaration_status === DeclarationStatus::Draft->value;
    }

    public function approve(User $user, InstitutionAnnualDeclaration $declaration): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}