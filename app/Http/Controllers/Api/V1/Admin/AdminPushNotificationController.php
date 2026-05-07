<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\AdminSendPushNotificationRequest;
use App\Models\Member;
use App\Services\Notifications\OneSignalPushService;
use Illuminate\Http\JsonResponse;

class AdminPushNotificationController extends BaseApiController
{
    public function store(AdminSendPushNotificationRequest $request, OneSignalPushService $oneSignal): JsonResponse
    {
        $payload = $request->validated();

        $audience = (string) ($payload['audience'] ?? 'all_members');
        $memberIds = $audience === 'member_ids' ? collect($payload['member_ids'] ?? [])->map(fn ($id) => (int) $id)->all() : [];

        $userIds = Member::query()
            ->when($memberIds !== [], fn ($q) => $q->whereIn('id', $memberIds))
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $data = [];
        if (! empty($payload['deep_link'])) {
            $data['deep_link'] = (string) $payload['deep_link'];
        }

        $result = $oneSignal->sendToUsers(
            $userIds,
            (string) $payload['title'],
            (string) $payload['message'],
            $data,
        );

        $recipients = (int) ($result['recipients'] ?? count($userIds));
        $warnings = [];
        if ($recipients <= 0) {
            $warnings[] = 'No subscribed OneSignal devices were reached for this audience.';
        }

        $providerErrors = $result['errors'] ?? [];
        if (is_string($providerErrors)) {
            $providerErrors = [$providerErrors];
        }
        if (! is_array($providerErrors)) {
            $providerErrors = [];
        }

        return $this->success('Push notification sent successfully.', [
            'notification_id' => $result['id'] ?? null,
            'recipients' => $recipients,
            'audience' => $audience,
            'targeted_users_count' => count($userIds),
            'targeted_aliases' => collect($userIds)->map(fn ($id) => 'user-'.(int) $id)->values()->all(),
            'provider_errors' => $providerErrors,
            'warnings' => $warnings,
        ]);
    }
}

