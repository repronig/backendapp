<?php

namespace App\Policies;

use App\Models\Association;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class AssociationPolicy
{
    use HandlesAdminOverride;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function view(User $user, Association $association): bool
    {
        return $user->associations()->where('associations.id', $association->id)->where('association_user.is_active', true)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, Association $association): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('association_officer')) {
            return false;
        }

        return $user->associations()
            ->where('associations.id', $association->id)
            ->where('association_user.is_active', true)
            ->where('associations.is_enabled', true)
            ->where('associations.status', 'active')
            ->exists();
    }

    public function delete(User $user, Association $association): bool
    {
        return $user->hasRole('super_admin');
    }

    public function reviewApplications(User $user): bool
    {
        return $user->hasRole('association_officer') && $user->associations()->where('associations.is_enabled', true)->where('association_user.is_active', true)->exists();
    }
}
