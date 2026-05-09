<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MemberApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'application_reference' => $this->application_reference,
            'user' => new UserResource($this->whenLoaded('user')),
            'association' => new AssociationResource($this->whenLoaded('association')),
            'applicant_type' => $this->applicant_type,
            'applicant_type_label' => $this->label($this->applicant_type),
            'member_author_type' => $this->member_author_type,
            'member_author_type_label' => $this->label($this->member_author_type),
            'member_author_category' => $this->member_author_category,
            'member_author_category_label' => $this->label($this->member_author_category),
            'application_status' => $this->application_status,
            'application_status_label' => $this->label($this->application_status),
            'affiliation_status' => $this->affiliation_status,
            'affiliation_status_label' => $this->label($this->affiliation_status),
            'submission_stage' => $this->submission_stage,
            'submission_stage_label' => $this->label($this->submission_stage),
            'nationality' => $this->nationality,
            'country_of_residence' => $this->country_of_residence,
            'is_diaspora' => $this->is_diaspora,
            'diaspora_label' => $this->is_diaspora ? 'Yes' : 'No',
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_owner_name' => $this->bank_account_owner_name,
            'next_of_kin_name' => $this->next_of_kin_name,
            'next_of_kin_phone' => $this->next_of_kin_phone,
            'publisher_organisation_name' => $this->publisher_organisation_name,
            'publisher_tin' => $this->publisher_tin,
            'publisher_location_address' => $this->publisher_location_address,
            'publisher_postal_address' => $this->publisher_postal_address,
            'publisher_email' => $this->publisher_email,
            'publisher_phone' => $this->publisher_phone,
            'consent_accepted' => (bool) $this->consent_accepted,
            'consent_date' => optional($this->consent_date)->toDateString(),
            'notes' => $this->notes,
            'affiliation_review_note' => $this->affiliation_review_note,
            'member_provided_id' => $this->member_provided_id,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'affiliation_reviewed_at' => $this->affiliation_reviewed_at,
            'documents' => MemberApplicationDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function label(?string $value): ?string
    {
        return $value ? Str::headline(str_replace(['_', '-'], ' ', $value)) : null;
    }
}
