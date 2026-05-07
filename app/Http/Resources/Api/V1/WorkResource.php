<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class WorkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'reference_number' => $this->reference_number,
            'member_id' => $this->member_id,
            'type_of_work' => $this->type_of_work,
            'type_of_work_label' => $this->label($this->type_of_work),
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'publication_year' => $this->publication_year,
            'synopsis' => $this->synopsis,
            'primary_language' => $this->primary_language,
            'work_format' => $this->work_format,
            'work_format_label' => $this->label($this->work_format),
            'identifier_type' => $this->identifier_type,
            'identifier_type_label' => $this->label($this->identifier_type),
            'identifier_value' => $this->identifier_value,
            'duplicate_fingerprint' => $this->when($request->user()?->hasAnyRole(['admin', 'super_admin']), $this->duplicate_fingerprint),
            'doi' => $this->doi,
            'publisher_name' => $this->publisher_name,
            'target_market' => $this->target_market,
            'target_market_label' => $this->label($this->target_market),
            'target_market_other' => $this->target_market_other,
            'production_status' => $this->production_status,
            'production_status_label' => $this->label($this->production_status),
            'agreement_accepted' => (bool) $this->agreement_accepted,
            'date_of_consent' => optional($this->date_of_consent)->toDateString(),
            'other_work_type' => $this->other_work_type,
            'notes' => $this->notes,
            'work_status' => $this->work_status,
            'work_status_label' => $this->label($this->work_status),
            'verification_status' => $this->verification_status,
            'verification_status_label' => $this->label($this->verification_status),
            'is_disputed' => $this->is_disputed,
            'is_restricted' => $this->is_restricted,
            'update_request_status' => $this->update_request_status,
            'update_request_status_label' => $this->label($this->update_request_status),
            'update_requested_at' => optional($this->update_requested_at)->toIso8601String(),
            'update_requested_by_member_id' => $this->update_requested_by_member_id,
            'update_request_note' => $this->update_request_note,
            'update_request_reviewed_at' => optional($this->update_request_reviewed_at)->toIso8601String(),
            'update_request_reviewed_by_user_id' => $this->update_request_reviewed_by_user_id,
            'update_request_review_note' => $this->update_request_review_note,
            'review_reason' => $this->review_reason,
            'governance_reason_code' => $this->governance_reason_code,
            'governance_reason' => $this->when($this->viewerMaySeeGovernanceReason($request), $this->governance_reason),
            'verified_at' => optional($this->verified_at)->toIso8601String(),
            'last_reviewed_at' => optional($this->last_reviewed_at)->toIso8601String(),
            'verified_by_user_id' => $this->verified_by_user_id,
            'last_reviewed_by_user_id' => $this->last_reviewed_by_user_id,
            'verified_by' => $this->whenLoaded(
                'verifier',
                fn () => $this->verifier ? new UserResource($this->verifier) : null
            ),
            'last_reviewed_by' => $this->whenLoaded(
                'lastReviewer',
                fn () => $this->lastReviewer ? new UserResource($this->lastReviewer) : null
            ),
            'submitted_at' => $this->submitted_at,
            'contributors' => WorkContributorResource::collection($this->whenLoaded('contributors')),
            'files' => WorkFileResource::collection($this->whenLoaded('files')),
            'reviews' => WorkReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function viewerMaySeeGovernanceReason(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        return $user->hasRole('member')
            && (int) optional($user->member)->id === (int) $this->member_id;
    }

    private function label(string|\BackedEnum|null $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Str::headline(str_replace(['_', '-'], ' ', $value));
    }
}
