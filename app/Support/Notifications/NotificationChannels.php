<?php

namespace App\Support\Notifications;

final class NotificationChannels
{
    public const EMAIL = 'email';
    public const SMS = 'sms';
    public const SYSTEM = 'system';

    public const ALL = [
        self::EMAIL,
        self::SMS,
        self::SYSTEM,
    ];

    private function __construct()
    {
    }
}
