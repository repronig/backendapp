<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision' => $this->decision,
            'reason_code' => $this->reason_code,
            'review_note' => $this->review_note,
            'evidence_requested' => $this->evidence_requested,
            'reviewed_at' => $this->reviewed_at,
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
                'email' => $this->reviewer?->email,
            ]),
            'metadata' => $this->metadata_json,
        ];
    }
}
