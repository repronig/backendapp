<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isStaffViewer = $request->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'body' => $this->body,
            'category' => $this->category instanceof \BackedEnum ? $this->category->value : $this->category,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'portal_context' => $this->portal_context instanceof \BackedEnum ? $this->portal_context->value : $this->portal_context,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ]),
            'replies' => SupportTicketReplyResource::collection($this->whenLoaded('replies')),
            'internal_notes' => $this->when(
                $isStaffViewer && $this->relationLoaded('internalNotes'),
                fn () => SupportTicketInternalNoteResource::collection($this->internalNotes)
            ),
        ];
    }
}
