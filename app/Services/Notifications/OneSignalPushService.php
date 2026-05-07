<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

class OneSignalPushService
{
    /**
     * @param array<int> $userIds
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sendToUsers(array $userIds, string $title, string $message, array $data = []): array
    {
        $appId = (string) config('services.onesignal.app_id', '');
        $apiKey = (string) config('services.onesignal.api_key', '');

        if ($appId === '' || $apiKey === '') {
            throw new \RuntimeException('OneSignal is not configured.');
        }

        $aliases = collect($userIds)
            ->map(fn ($id) => 'user-'.(int) $id)
            ->unique()
            ->values()
            ->all();

        if ($aliases === []) {
            return [
                'id' => null,
                'recipients' => 0,
            ];
        }

        $payload = [
            'app_id' => $appId,
            'include_external_user_ids' => $aliases,
            'channel_for_external_user_ids' => 'push',
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        if ($data !== []) {
            $payload['data'] = $data;
        }

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => 'Basic '.$apiKey])
            ->post('https://onesignal.com/api/v1/notifications', $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }
}

