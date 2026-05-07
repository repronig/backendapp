<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TimelineEventResource extends JsonResource
{
    protected function eventType(string $action): string
    {
        return match (true) {
            Str::contains($action, ['approve', 'reactivate', 'activate', 'issue']) => 'status_change',
            Str::contains($action, ['reject', 'deactivate', 'disable', 'suspend']) => 'status_change',
            Str::contains($action, ['upload', 'document']) => 'document',
            Str::contains($action, ['logo', 'avatar', 'profile']) => 'profile',
            Str::contains($action, ['payment', 'invoice']) => 'payment',
            Str::contains($action, ['licence']) => 'licensing',
            default => 'activity',
        };
    }

    protected function descriptionFor(string $action): string
    {
        return match (true) {
            Str::contains($action, 'moved_to_review') => 'Moved into review workflow.',
            Str::contains($action, 'approved') => 'Approved and recorded by an authorized user.',
            Str::contains($action, 'rejected') => 'Rejected with a recorded decision.',
            Str::contains($action, ['deactivate', 'disabled']) => 'Deactivated or disabled by an authorized user.',
            Str::contains($action, ['reactivate', 'enabled', 'activate']) => 'Reactivated or enabled by an authorized user.',
            Str::contains($action, ['document', 'upload']) => 'A document or supporting file was uploaded.',
            Str::contains($action, ['logo', 'avatar']) => 'A brand or profile image was updated.',
            Str::contains($action, 'payment') => 'A payment-related event was recorded.',
            Str::contains($action, 'licence') => 'A licence-related event was recorded.',
            default => Str::headline(str_replace(['_', '-'], ' ', $action)).'.',
        };
    }

    public function toArray(Request $request): array
    {
        $action = (string) $this->action;

        return [
            'id' => $this->id,
            'type' => $this->eventType($action),
            'action' => $action,
            'label' => Str::headline(str_replace(['_', '-'], ' ', $action)),
            'description' => $this->descriptionFor($action),
            'actor' => new UserResource($this->whenLoaded('actor')),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'before' => $this->before_json,
            'after' => $this->after_json,
            'timestamp' => $this->created_at,
            'created_at' => $this->created_at,
        ];
    }
}
