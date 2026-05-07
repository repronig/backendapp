<?php

namespace App\Notifications\System;

use App\Support\Notifications\NotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseSystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function basePayload(
        string $type,
        string $title,
        string $message,
        string $severity = 'info',
        ?string $actionUrl = null,
        array $meta = []
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'channel' => NotificationChannels::SYSTEM,
            'action_url' => $actionUrl,
            'meta' => $meta,
        ];
    }
}
