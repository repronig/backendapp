<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Notifications\NotificationChannels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $channel = match ($this->channel) {
            'mail' => NotificationChannels::EMAIL,
            'database', 'broadcast' => NotificationChannels::SYSTEM,
            default => $this->channel,
        };

        return [
            'id' => $this->id,
            'channel' => $channel,
            'notification_key' => $this->notification_key,
            'event_key' => $this->notification_key,
            'is_enabled' => (bool) $this->is_enabled,
            'enabled' => (bool) $this->is_enabled,
        ];
    }
}
