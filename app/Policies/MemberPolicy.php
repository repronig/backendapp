<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

/**
 * Admin and super admin bypass via {@see HandlesAdminOverride::before()}.
 * Member-facing rules can be expanded when members need direct member record access.
 */
class MemberPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, Member $member): bool
    {
        return false;
    }

    public function delete(User $user, Member $member): bool
    {
        return false;
    }
}
