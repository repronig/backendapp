<?php

namespace App\Actions\Institutions;

use App\Actions\Audit\LogAuditAction;
use App\Models\Institution;
use App\Models\InstitutionProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateInstitutionProfileAction
{
    private const ACADEMIC_INSTITUTION_TYPES = [
        'university',
        'polytechnic',
        'college_of_education',
        'research_institute',
    ];

    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Institution $institution,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Institution {
        return DB::transaction(function () use ($institution, $data, $actor, $ipAddress, $userAgent) {
            $beforeInstitution = $institution->toArray();
            $beforeProfile = $institution->profile?->toArray();

            $institutionType = $institution->account_status === 'active'
                ? $institution->institution_type
                : ($data['institution_type'] ?? $institution->institution_type);
            $usesAcademicMetrics = in_array($institutionType, self::ACADEMIC_INSTITUTION_TYPES, true);

            $institution->update([
                'institution_type' => $institutionType,
                'contact_person_name' => $data['contact_person_name'] ?? $institution->contact_person_name,
                'contact_person_title' => $data['contact_person_title'] ?? $institution->contact_person_title,
                'phone' => $data['phone'] ?? $institution->phone,
                'address_line_1' => $data['address_line_1'] ?? $institution->address_line_1,
                'address_line_2' => $data['address_line_2'] ?? $institution->address_line_2,
                'city' => $data['city'] ?? $institution->city,
                'state' => $data['state'] ?? $institution->state,
                'country' => $data['country'] ?? $institution->country,
                'postal_code' => $data['postal_code'] ?? $institution->postal_code,
                'year_established' => $data['year_established'] ?? $institution->year_established,
                'faculties_count' => $usesAcademicMetrics ? ($data['faculties_count'] ?? $institution->faculties_count) : null,
                'member_count' => $usesAcademicMetrics ? null : ($data['member_count'] ?? $institution->member_count),
                'branches_count' => $usesAcademicMetrics ? null : ($data['branches_count'] ?? $institution->branches_count),
                'onboarding_status' => $institution->onboarding_status === 'draft' ? 'submitted' : $institution->onboarding_status,
            ]);

            $profile = InstitutionProfile::updateOrCreate(
                ['institution_id' => $institution->id],
                [
                    'academic_staff_count' => $usesAcademicMetrics ? ($data['academic_staff_count'] ?? $institution->profile?->academic_staff_count) : null,
                    'administrative_staff_count' => $usesAcademicMetrics ? ($data['administrative_staff_count'] ?? $institution->profile?->administrative_staff_count) : null,
                    'campuses_count' => $usesAcademicMetrics ? ($data['campuses_count'] ?? $institution->profile?->campuses_count) : null,
                    'metadata_json' => $institution->profile?->metadata_json,
                ]
            );

            $freshInstitution = $institution->fresh(['profile', 'latestAnnualDeclaration']);
            $freshProfile = $profile->fresh();

            $this->logAuditAction->execute(
                $actor,
                'institution_profile_context_updated',
                $freshInstitution,
                $beforeInstitution,
                $freshInstitution->toArray(),
                $ipAddress,
                $userAgent
            );

            $this->logAuditAction->execute(
                $actor,
                $beforeProfile ? 'institution_profile_updated' : 'institution_profile_created',
                $freshProfile,
                $beforeProfile,
                $freshProfile->toArray(),
                $ipAddress,
                $userAgent
            );

            return $freshInstitution;
        });
    }
}
