<?php

namespace App\Actions\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Support\Notifications\NotificationChannels;
use App\Support\Notifications\NotificationPreferenceKeys;

class UpdateNotificationPreferencesAction
{
    public function execute(User $user, array $preferences): array
    {
        $saved = [];

        foreach ($preferences as $preference) {
            $saved[] = NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'channel' => $this->normalizeChannel($preference['channel']),
                    'notification_key' => NotificationPreferenceKeys::normalize($preference['notification_key'] ?? $preference['event_key']),
                ],
                ['is_enabled' => (bool) ($preference['is_enabled'] ?? $preference['enabled'])]
            );
        }

        return $saved;
    }

    protected function normalizeChannel(string $channel): string
    {
        return match ($channel) {
            'mail' => NotificationChannels::EMAIL,
            'database', 'broadcast' => NotificationChannels::SYSTEM,
            default => $channel,
        };
    }
}
