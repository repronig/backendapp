<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\User;
use App\Support\Notifications\NotificationChannels;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemNotificationService
{
    public function __construct(
        protected NotificationPreferenceResolver $preferenceResolver
    ) {}

    public function send(User $user, Notification $notification, string $notificationKey, ?string $subject = null, array $context = []): void
    {
        $payload = method_exists($notification, 'toArray')
            ? (array) $notification->toArray($user)
            : [];
        $idempotencyKey = $this->buildIdempotencyKey($user, $notification, $notificationKey, $subject, $payload, $context);

        if ($this->deliveryAlreadyLogged($idempotencyKey)) {
            return;
        }

        if (! $this->preferenceResolver->shouldSend($user, $notificationKey, NotificationChannels::SYSTEM)) {
            NotificationLog::create([
                'user_id' => $user->id,
                'notification_key' => $notificationKey,
                'channel' => NotificationChannels::SYSTEM,
                'idempotency_key' => $idempotencyKey,
                'status' => 'skipped',
                'subject' => $subject,
                'payload_json' => $payload,
                'failure_reason' => 'Notification preference disabled for system channel.',
            ]);

            return;
        }

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'notification_key' => $notificationKey,
            'channel' => NotificationChannels::SYSTEM,
            'idempotency_key' => $idempotencyKey,
            'status' => 'queued',
            'subject' => $subject,
            'payload_json' => $payload,
        ]);

        try {
            $user->notify($notification);

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('system_notification_failed', [
                'user_id' => $user->id,
                'notification_key' => $notificationKey,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function deliveryAlreadyLogged(string $idempotencyKey): bool
    {
        return NotificationLog::query()
            ->where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['queued', 'sent', 'skipped'])
            ->exists();
    }

    protected function buildIdempotencyKey(
        User $user,
        Notification $notification,
        string $notificationKey,
        ?string $subject,
        array $payload,
        array $context = []
    ): string {
        $normalizedPayload = Arr::sortRecursive($payload);
        $normalizedContext = Arr::sortRecursive($context);

        return hash('sha256', json_encode([
            'channel' => NotificationChannels::SYSTEM,
            'user_id' => $user->id,
            'notification_key' => $notificationKey,
            'subject' => $subject,
            'notification' => get_class($notification),
            'payload' => $normalizedPayload,
            'context' => $normalizedContext,
        ], JSON_THROW_ON_ERROR));
    }
}
