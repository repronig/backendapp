<?php

namespace App\Actions\Notifications;

use App\Models\User;

class MarkAllNotificationsReadAction
{
    public function execute(User $user): int
    {
        return $user->unreadNotifications()->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
