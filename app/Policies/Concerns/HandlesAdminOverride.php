<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Grants `admin` and `super_admin` a blanket pass before per-ability Policy checks.
 *
 * Route middleware still restricts which portal hits the controller; this trait only
 * short-circuits model authorization for staff roles. For behaviour that must be
 * limited to `super_admin`, implement it on the Policy method body — do not rely on this trait alone.
 *
 * @see docs/authorization-strategy.md
 */
trait HandlesAdminOverride
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }
}
