<?php

namespace App\Actions\Dashboard;

use App\Models\User;

class BuildAdminAccessSummaryAction
{
    public function execute(User $user): ?array
    {
        if (! $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
            return null;
        }

        return [
            'can_access_admin_panel' => true,
            'can_access_super_panel' => $user->hasRole('super_admin'),
        ];
    }
}