<?php

namespace App\Actions\Institutions;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolveInstitutionForUserAction
{
    public function execute(User $user): Institution
    {
        $link = $user->institutionUsers()
            ->where('institution_users.is_active', true)
            ->join('institutions', 'institutions.id', '=', 'institution_users.institution_id')
            ->whereIn('institutions.account_status', ['pending_review', 'active'])
            ->where(function ($query) {
                $query->whereNull('institutions.governance_status')
                    ->orWhereIn('institutions.governance_status', ['normal', 'restricted']);
            })
            ->orderByDesc('institution_users.is_primary')
            ->select('institution_users.*')
            ->first();

        if (! $link) {
            throw new ModelNotFoundException('Active institution mapping not found for user.');
        }

        return Institution::query()
            ->with(['profile', 'state', 'city'])
            ->findOrFail($link->institution_id);
    }
}