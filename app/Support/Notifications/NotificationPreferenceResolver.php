<?php

namespace App\Support\Notifications;

use App\Models\User;

class NotificationPreferenceResolver
{
    public function shouldSend(User $user, string $notificationKey, string $channel): bool
    {
        if (! in_array($channel, NotificationChannels::ALL, true)) {
            return false;
        }

        // Critical transactional email: must not be blocked by “account security” digests toggles.
        if ($channel === NotificationChannels::EMAIL && $notificationKey === 'password_reset') {
            return true;
        }

        $normalized = NotificationPreferenceKeys::normalize($notificationKey);
        $keysToMatch = array_values(array_unique([$notificationKey, $normalized]));

        $preference = $user->notificationPreferences()
            ->whereIn('notification_key', $keysToMatch)
            ->where('channel', $channel)
            ->orderByRaw('CASE WHEN notification_key = ? THEN 0 ELSE 1 END', [$notificationKey])
            ->first();

        if ($preference === null) {
            return true;
        }

        return (bool) $preference->is_enabled;
    }
}
