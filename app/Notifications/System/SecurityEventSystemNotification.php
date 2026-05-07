<?php

namespace App\Notifications\System;

class SecurityEventSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected string $eventType,
        protected string $title,
        protected string $message,
        protected ?string $actionUrl = '/admin/settings',
        protected array $meta = [],
    ) {
    }

    public function toArray(object $notifiable): array
    {
        return [
            ...$this->basePayload(
                $this->eventType,
                $this->title,
                $this->message,
                'info',
                $this->actionUrl,
                $this->meta,
            ),
            'category' => 'security',
        ];
    }
}
