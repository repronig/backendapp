<?php

namespace App\Policies;

use App\Models\Association;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Member;
use App\Models\User;

class DocumentPolicy
{
    public function delete(User $user, Document $document): bool
    {
        $documentable = $document->documentable;

        if ($documentable instanceof Member) {
            return (int) $documentable->user_id === (int) $user->id;
        }

        if ($documentable instanceof Institution) {
            return $user->institutionUsers()
                ->where('institution_id', $documentable->id)
                ->where('is_active', true)
                ->exists();
        }

        if ($documentable instanceof Association) {
            return $user->associations()
                ->where('association_id', $documentable->id)
                ->wherePivot('is_active', true)
                ->exists();
        }

        return false;
    }
}
