<?php

namespace App\Policies;

use App\Enums\MemberApplicationStatus;
use App\Models\MemberApplication;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class MemberApplicationPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, MemberApplication $memberApplication): bool
    {
        if ($user->hasRole('member')) {
            return (int) $memberApplication->user_id === (int) $user->id;
        }

        if ($user->hasRole('association_officer')) {
            return $user->associations()
                ->where('associations.id', $memberApplication->association_id)
                ->where('associations.status', 'active')
                ->where('associations.is_enabled', true)
                ->where('association_user.is_active', true)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasRole('member') || $user->member()->exists()) {
            return false;
        }

        $existingApplication = $user->memberApplication;

        if (! $existingApplication) {
            return true;
        }

        return $existingApplication->isEditableByApplicant();
    }

    public function update(User $user, MemberApplication $memberApplication): bool
    {
        return $user->hasRole('member')
            && (int) $memberApplication->user_id === (int) $user->id
            && $memberApplication->isEditableByApplicant();
    }

    public function submit(User $user, MemberApplication $memberApplication): bool
    {
        return $this->update($user, $memberApplication);
    }

    public function review(User $user, MemberApplication $memberApplication): bool
    {
        return $user->hasRole('association_officer')
            && $user->associations()
                ->where('associations.id', $memberApplication->association_id)
                ->where('associations.status', 'active')
                ->where('associations.is_enabled', true)
                ->where('association_user.is_active', true)
                ->exists();
    }
}