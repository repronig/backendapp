<?php

namespace App\Policies;

use App\Models\MemberApplicationDocument;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class MemberApplicationDocumentPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, MemberApplicationDocument $document): bool
    {
        $document->loadMissing('application');

        return $document->application !== null
            && app(MemberApplicationPolicy::class)->view($user, $document->application);
    }

    public function delete(User $user, MemberApplicationDocument $document): bool
    {
        $document->loadMissing('application');

        return $document->application !== null
            && app(MemberApplicationPolicy::class)->update($user, $document->application);
    }
}