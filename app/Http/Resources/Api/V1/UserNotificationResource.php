<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];

        return [
            'id' => $this->id,
            'type' => $data['type'] ?? class_basename((string) $this->type),
            'category' => $data['category'] ?? $data['type'] ?? class_basename((string) $this->type),
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'channel' => $data['channel'] ?? 'system',
            'read_at' => optional($this->read_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'action_url' => $data['action_url'] ?? null,
            'meta' => $data['meta'] ?? [],
        ];
    }
}
