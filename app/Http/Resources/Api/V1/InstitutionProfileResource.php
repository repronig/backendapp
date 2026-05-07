<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ProfileCompleteness;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class InstitutionProfileResource extends JsonResource
{
    private const ACADEMIC_INSTITUTION_TYPES = [
        'university',
        'polytechnic',
        'college_of_education',
        'research_institute',
    ];

    public function toArray(Request $request): array
    {
        $kycDocuments = $this->whenLoaded('legacyDocuments', fn () => $this->legacyDocuments, collect());
        $submittedKycTypes = $kycDocuments->pluck('document_type')->filter()->unique();

        $usesAcademicMetrics = in_array((string) $this->institution_type, self::ACADEMIC_INSTITUTION_TYPES, true);
        $metricCompletenessFields = $usesAcademicMetrics
            ? [
                'faculties_count' => $this->faculties_count,
                'academic_staff_count' => $this->profile?->academic_staff_count,
                'administrative_staff_count' => $this->profile?->administrative_staff_count,
                'campuses_count' => $this->profile?->campuses_count,
            ]
            : [
                'member_count' => $this->member_count,
                'branches_count' => $this->branches_count,
            ];

        $requiredKycTypes = ['cac_certificate', 'proof_of_address'];
        $missingKycTypes = collect($requiredKycTypes)->reject(fn (string $type) => $submittedKycTypes->contains($type))->values();

        $completeness = ProfileCompleteness::make(array_merge([
            'name' => $this->name,
            'institution_type' => $this->institution_type,
            'institution_type_label' => $this->institution_type ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->institution_type)) : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_person_name' => $this->contact_person_name,
            'contact_person_title' => $this->contact_person_title,
            'address_line_1' => $this->address_line_1,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'year_established' => $this->year_established,
            'logo' => $this->logo_url,
            'cac_certificate' => $submittedKycTypes->contains('cac_certificate'),
            'proof_of_address' => $submittedKycTypes->contains('proof_of_address'),
        ], $metricCompletenessFields));

        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'institution_type' => $this->institution_type,
            'institution_type_label' => $this->institution_type ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->institution_type)) : null,
            'registration_number' => $this->registration_number,
            'licence_id' => $this->licence_id,
            'year_established' => $this->year_established,
            'email' => $this->email,
            'phone' => $this->phone,

            'address' => [
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,

                // legacy-friendly keys
                'city' => $this->city,
                'state' => $this->state,

                // explicit frontend-safe keys
                'city_name' => $this->city,
                'state_name' => $this->state,
                'city_id' => $this->city_id,
                'state_id' => $this->state_id,

                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],

            // explicit structured location payload
            'location' => [
                'city_id' => $this->city_id,
                'state_id' => $this->state_id,
                'city_name' => $this->city,
                'state_name' => $this->state,
            ],

            'state' => $this->whenLoaded('state', fn () => new StateResource($this->resource->getRelation('state'))),
            'city' => $this->whenLoaded('city', fn () => new CityResource($this->resource->getRelation('city'))),

            'contact_person_name' => $this->contact_person_name,
            'contact_person_title' => $this->contact_person_title,
            'onboarding_status' => $this->onboarding_status,
            'onboarding_status_label' => $this->onboarding_status ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->onboarding_status)) : null,

            'faculties_count' => $this->faculties_count,
            'member_count' => $this->member_count,
            'branches_count' => $this->branches_count,

            'academic_staff_count' => $this->profile?->academic_staff_count,
            'administrative_staff_count' => $this->profile?->administrative_staff_count,
            'campuses_count' => $this->profile?->campuses_count,
            'profile_metadata_json' => $this->profile?->metadata_json,

            'account_status' => $this->account_status,
            'account_status_label' => $this->account_status ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->account_status)) : null,
            'governance_status_label' => $this->governance_status ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->governance_status)) : null,
            'governance_status' => $this->governance_status,
            'governance_reason_code' => $this->governance_reason_code,
            'governance_reason' => $this->governance_reason,
            'governance_changed_by_user_id' => $this->governance_changed_by_user_id,
            'governance_changed_at' => $this->governance_changed_at,
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_at' => $this->approved_at,
            'licence_id_generated_at' => $this->licence_id_generated_at,

            'licensing_terms_accepted_at' => optional($this->licensing_terms_accepted_at)?->toIso8601String(),
            'licensing_terms_acknowledged_on' => optional($this->licensing_terms_acknowledged_on)?->toDateString(),
            'licensing_terms_version_accepted' => $this->licensing_terms_version_accepted,

            'profile_completeness' => $completeness,
            'logo_url' => $this->logo_url,
            'logo_thumb_url' => $this->logo_thumb_url,
            'logo_medium_url' => $this->logo_medium_url,
            'kyc_readiness' => [
                'required_documents' => $requiredKycTypes,
                'submitted_documents' => $submittedKycTypes->values()->all(),
                'missing_documents' => $missingKycTypes->all(),
                'is_complete' => $missingKycTypes->isEmpty(),
            ],
            'kyc_documents' => InstitutionDocumentResource::collection($this->whenLoaded('legacyDocuments')),
        ];
    }
}
