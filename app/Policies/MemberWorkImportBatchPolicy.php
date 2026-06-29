<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

class MemberWorkImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('member') && $user->member !== null;
    }

    public function view(User $user, ImportBatch $importBatch): bool
    {
        return $this->ownsBatch($user, $importBatch);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('member') && $user->member !== null;
    }

    public function process(User $user, ImportBatch $importBatch): bool
    {
        return $this->ownsBatch($user, $importBatch);
    }

    public function uploadFiles(User $user, ImportBatch $importBatch): bool
    {
        return $this->ownsBatch($user, $importBatch);
    }

    public function submitReady(User $user, ImportBatch $importBatch): bool
    {
        return $this->ownsBatch($user, $importBatch);
    }

    protected function ownsBatch(User $user, ImportBatch $importBatch): bool
    {
        if (! $importBatch->isMemberWorkImport()) {
            return false;
        }

        $member = $user->member;

        return $member !== null && (int) $importBatch->member_id === (int) $member->id;
    }
}
