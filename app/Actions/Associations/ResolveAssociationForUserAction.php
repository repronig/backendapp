<?php

namespace App\Actions\Associations;

use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolveAssociationForUserAction
{
    public function execute(User $user): Association
    {
        $association = $user->associations()
            ->where('association_user.is_active', true)
            ->where('associations.is_enabled', true)
            ->where('associations.status', 'active')
            ->with(['state', 'city'])
            ->orderBy('associations.name')
            ->first();

        if (! $association) {
            throw new ModelNotFoundException('Active association mapping not found for user.');
        }

        return $association;
    }
}
