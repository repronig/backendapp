<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Notifications\UpdateNotificationPreferencesAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UpdateNotificationPreferencesRequest;
use App\Http\Resources\Api\V1\NotificationPreferenceResource;
use App\Models\NotificationPreference;
use App\Support\Notifications\NotificationChannels;
use App\Support\Notifications\NotificationPreferenceKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $existing = $request->user()
            ->notificationPreferences()
            ->get()
            ->keyBy(fn (NotificationPreference $preference) => $preference->notification_key.':'.$preference->channel);

        $preferences = collect(NotificationPreferenceKeys::TAXONOMY)
            ->flatMap(fn (string $key) => collect([NotificationChannels::EMAIL, NotificationChannels::SYSTEM])->map(function (string $channel) use ($key, $existing) {
                return $existing->get($key.':'.$channel) ?? new NotificationPreference([
                    'notification_key' => $key,
                    'channel' => $channel,
                    'is_enabled' => true,
                ]);
            }))
            ->values();

        return $this->success('Notification preferences retrieved successfully.', NotificationPreferenceResource::collection($preferences));
    }

    public function update(UpdateNotificationPreferencesRequest $request, UpdateNotificationPreferencesAction $action): JsonResponse
    {
        $preferences = $action->execute($request->user(), $request->validated('preferences'));

        return $this->success('Notification preferences updated successfully.', NotificationPreferenceResource::collection(collect($preferences)));
    }
}
