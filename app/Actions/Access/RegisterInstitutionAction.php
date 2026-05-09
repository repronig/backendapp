<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Security\StartSecurityChallengeAction;
use App\Models\Institution;
use App\Models\InstitutionProfile;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterInstitutionAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected StartSecurityChallengeAction $startSecurityChallengeAction
    ) {}

    public function execute(array $data, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent) {
            $user = User::create([
                'first_name' => $data['contact_person_name'],
                'last_name' => '',
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'account_type' => 'institution_user',
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            $user->assignRole('institution_user');

            $institution = Institution::create([
                'name' => $data['organisation_name'],
                'institution_type' => $data['institution_type'],
                'registration_number' => $data['registration_number'] ?? null,
                'year_established' => $data['year_established'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'contact_person_name' => $data['contact_person_name'],
                'contact_person_title' => $data['contact_person_title'] ?? null,
                'faculties_count' => null,
                'member_count' => $data['member_count'] ?? null,
                'branches_count' => $data['branches_count'] ?? null,
                'onboarding_status' => 'submitted',
                'account_status' => 'pending_review',
                'address_line_1' => $data['address_line_1'] ?? null,
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? 'NG',
                'postal_code' => $data['postal_code'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
            ]);

            $profile = InstitutionProfile::create([
                'institution_id' => $institution->id,
                'academic_staff_count' => $data['academic_staff_count'] ?? null,
                'administrative_staff_count' => $data['administrative_staff_count'] ?? null,
                'campuses_count' => $data['campuses_count'] ?? null,
                'metadata_json' => ['registration_number' => $data['registration_number'] ?? null],
            ]);

            $institutionUser = InstitutionUser::create([
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'role_label' => 'primary_contact',
                'is_primary' => true,
                'is_active' => true,
            ]);

            $freshUser = $user->fresh()->load('roles');
            $freshInstitution = $institution->fresh(['profile', 'documents', 'institutionUsers', 'state', 'city']);
            $freshInstitutionUser = $institutionUser->fresh();

            $this->logAuditAction->execute($freshUser, 'institution_user_registered', $freshUser, null, $freshUser->toArray(), $ipAddress, $userAgent);
            $this->logAuditAction->execute($freshUser, 'institution_created_from_registration', $freshInstitution, null, $freshInstitution->toArray(), $ipAddress, $userAgent);
            $this->logAuditAction->execute($freshUser, 'institution_user_link_created', $freshInstitutionUser, null, $freshInstitutionUser->toArray(), $ipAddress, $userAgent);
            $this->logAuditAction->execute($freshUser, 'institution_profile_created', $profile, null, $profile->toArray(), $ipAddress, $userAgent);

            $challenge = $this->startSecurityChallengeAction->execute($freshUser, 'institution_registration_otp');

            return [
                'user' => $freshUser,
                'institution' => $freshInstitution,
                'otp_expires_at' => $challenge['expires_at'],
                'otp_delivery' => $challenge['delivery'] ?? null,
            ];
        });
    }
}
