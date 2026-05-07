<?php

namespace App\Actions\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListNotificationsAction
{
    public function execute(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate($perPage);
    }
}
