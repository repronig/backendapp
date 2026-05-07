<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkFile;
use App\Policies\Concerns\HandlesAdminOverride;

class WorkFilePolicy
{
    use HandlesAdminOverride;

    public function view(User $user, WorkFile $file): bool
    {
        $file->loadMissing('work');

        return $file->work !== null
            && app(WorkPolicy::class)->view($user, $file->work);
    }

    public function delete(User $user, WorkFile $file): bool
    {
        $file->loadMissing('work');

        return $file->work !== null
            && app(WorkPolicy::class)->update($user, $file->work);
    }
}