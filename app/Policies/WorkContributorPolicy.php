<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkContributor;
use App\Policies\Concerns\HandlesAdminOverride;

class WorkContributorPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, WorkContributor $contributor): bool
    {
        $contributor->loadMissing('work');

        return $contributor->work !== null
            && app(WorkPolicy::class)->view($user, $contributor->work);
    }

    public function update(User $user, WorkContributor $contributor): bool
    {
        $contributor->loadMissing('work');

        return $contributor->work !== null
            && app(WorkPolicy::class)->update($user, $contributor->work);
    }

    public function delete(User $user, WorkContributor $contributor): bool
    {
        return $this->update($user, $contributor);
    }
}