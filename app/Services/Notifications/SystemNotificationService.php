<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\User;
use App\Support\Notifications\NotificationChannels;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

class SystemNotificationService
{
    public function __construct(
        protected NotificationPreferenceResolver $preferenceResolver
    ) {}

    public function send(User $user, Notification $notification, string $notificationKey, ?string $subject = null, array $context = [], bool $dispatchSynchronously = false): void
    {
        $payload = method_exists($notification, 'toArray')
            ? (array) $notification->toArray($user)
            : [];
        $idempotencyKey = $this->buildIdempotencyKey($user, $notification, $notificationKey, $subject, $payload, $context);

        if ($this->shouldSkipDuplicateSystemDelivery($idempotencyKey)) {
            return;
        }

        if (! $this->preferenceResolver->shouldSend($user, $notificationKey, NotificationChannels::SYSTEM)) {
            NotificationLog::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'user_id' => $user->id,
                    'notification_key' => $notificationKey,
                    'channel' => NotificationChannels::SYSTEM,
                    'status' => 'skipped',
                    'subject' => $subject,
                    'payload_json' => $payload,
                    'failure_reason' => 'Notification preference disabled for system channel.',
                    'sent_at' => null,
                    'failed_at' => null,
                ]
            );

            return;
        }

        $log = NotificationLog::updateOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'user_id' => $user->id,
                'notification_key' => $notificationKey,
                'channel' => NotificationChannels::SYSTEM,
                'status' => 'queued',
                'subject' => $subject,
                'payload_json' => $payload,
                'failure_reason' => null,
                'failed_at' => null,
                'sent_at' => null,
            ]
        );

        try {
            if ($dispatchSynchronously) {
                NotificationFacade::sendNow($user, $notification);
            } else {
                $user->notify($notification);
            }

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

    /**
     * Block duplicate sends for terminal rows (sent, skipped) and for in-flight rows (queued
     * updated within the last 30 minutes). Older queued rows are allowed through so a new
     * attempt can run after a worker crash; failed rows are also allowed so updateOrCreate
     * can reuse the same idempotency key without a unique constraint violation.
     */
    protected function shouldSkipDuplicateSystemDelivery(string $idempotencyKey): bool
    {
        $row = NotificationLog::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($row === null) {
            return false;
        }

        if (in_array($row->status, ['sent', 'skipped'], true)) {
            return true;
        }

        if ($row->status === 'queued' && $row->updated_at && $row->updated_at->gt(now()->subMinutes(30))) {
            return true;
        }

        return false;
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
