<?php

namespace App\Actions\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

class MarkNotificationReadAction
{
    public function execute(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()
            ->whereKey($notificationId)
            ->first();

        if (! $notification instanceof DatabaseNotification) {
            throw new ModelNotFoundException('Notification not found.');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
            $notification->refresh();
        }

        return $notification;
    }
}
