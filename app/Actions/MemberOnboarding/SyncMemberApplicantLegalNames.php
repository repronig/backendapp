<?php

namespace App\Actions\MemberOnboarding;

use App\Models\MemberApplication;
use App\Models\User;

final class SyncMemberApplicantLegalNames
{
    /**
     * Persist first/last name on the applicant user and remove those keys from $data
     * so they are not mass-assigned onto {@see MemberApplication}.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromApplicationPayload(User $user, array &$data): void
    {
        $first = $data['first_name'] ?? null;
        $last = $data['last_name'] ?? null;
        unset($data['first_name'], $data['last_name']);

        if (! is_string($first) || trim($first) === '' || ! is_string($last) || trim($last) === '') {
            return;
        }

        $user->update([
            'first_name' => trim($first),
            'last_name' => trim($last),
        ]);
    }
}
