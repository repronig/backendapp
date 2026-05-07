<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationOutboxEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'operation' => $this->operation,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'last_error' => $this->last_error,
            'scheduled_at' => optional($this->scheduled_at)->toIso8601String(),
            'processed_at' => optional($this->processed_at)->toIso8601String(),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'payload' => $this->payload,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
