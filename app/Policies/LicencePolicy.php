<?php

namespace App\Policies;

use App\Enums\LicenceStatus;
use App\Models\Licence;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class LicencePolicy
{
    use HandlesAdminOverride;

    public function view(User $user, Licence $licence): bool
    {
        return $user->hasRole('institution_user')
            && $user->institutionUsers()
                ->join('institutions', 'institutions.id', '=', 'institution_users.institution_id')
                ->where('institution_users.institution_id', $licence->institution_id)
                ->where('institution_users.is_active', true)
                ->where('institutions.account_status', 'active')
                ->where(function ($query) {
                    $query->whereNull('institutions.governance_status')
                        ->orWhereIn('institutions.governance_status', ['normal', 'restricted']);
                })
                ->exists();
    }

    public function initiatePayment(User $user, Licence $licence): bool
    {
        return $this->view($user, $licence)
            && in_array($licence->licence_status, ['pending_payment', 'active'], true)
            && (float) $licence->outstanding_amount > 0;
    }

    public function viewPayments(User $user, Licence $licence): bool
    {
        return $this->view($user, $licence);
    }

    public function downloadCertificate(User $user, Licence $licence): bool
    {
        return $this->view($user, $licence)
            && in_array($licence->licence_status, [
                LicenceStatus::Active->value,
                LicenceStatus::Expired->value,
            ], true);
    }

    public function declareUsage(User $user, Licence $licence): bool
    {
        return $this->view($user, $licence);
    }
}
