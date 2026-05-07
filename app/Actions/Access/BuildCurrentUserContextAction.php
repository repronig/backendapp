<?php

namespace App\Actions\Access;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Http\Resources\Api\V1\InstitutionProfileResource;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Http\Resources\Api\V1\MemberProfileResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\Payments\PaymentGatewaySettings;
use Throwable;

class BuildCurrentUserContextAction
{
    public function __construct(
        private readonly ResolveInstitutionForUserAction $resolveInstitutionForUserAction,
        private readonly PaymentGatewaySettings $paymentGatewaySettings,
    ) {}

    public function execute(User $user): array
    {
        $user->load([
            'roles',
            'associations.state',
            'associations.city',
            'member.association',
            'member.profile',
            'institutionUsers.institution.profile',
            'institutionUsers.institution.state',
            'institutionUsers.institution.city',
            'primaryInstitutionUser.institution',
        ]);

        $member = $user->member?->load(['user.roles', 'association', 'profile']);

        $memberApplication = $user->memberApplication()?->with([
            'user.roles',
            'association.state',
            'association.city',
            'documents',
        ])->first();

        $institution = null;

        try {
            if ($user->hasRole('institution_user')) {
                $institution = $this->resolveInstitutionForUserAction->execute($user)->load(['profile', 'legacyDocuments']);
            }
        } catch (Throwable) {
            $institution = null;
        }

        $roles = $user->getRoleNames()->values()->all();

        $activeAssociation = null;
        if ($user->relationLoaded('associations') && $user->associations->isNotEmpty()) {
            $activeAssociation = $user->associations->first(function ($association) {
                return (bool) ($association->pivot?->is_active)
                    && $association->status === 'active'
                    && (bool) $association->is_enabled;
            });
        }

        $primaryInstitutionUser = $user->primaryInstitutionUser;
        $institutionLinkIsActive = (bool) ($primaryInstitutionUser?->is_active);

        $memberApplicationStatus = $memberApplication?->application_status;
        $memberSubmissionStage = $memberApplication?->submission_stage;
        $institutionOnboardingStatus = $institution?->onboarding_status;
        $institutionAccountStatus = $institution?->account_status;
        $institutionGovernanceStatus = $institution?->governance_status;

        $licensingTermsConfigured = $this->paymentGatewaySettings->configuredInstitutionLicensingTerms() !== null;
        $institutionLicensingTermsAcceptanceRequired = $institution !== null
            && $licensingTermsConfigured
            && $institution->licensing_terms_accepted_at === null;

        return [
            'user' => new UserResource($user),

            'role_summary' => [
                'roles' => $roles,
                'primary_role' => $roles[0] ?? null,
                'is_member' => in_array('member', $roles, true),
                'is_association_officer' => in_array('association_officer', $roles, true),
                'is_institution_user' => in_array('institution_user', $roles, true),
                'is_admin' => in_array('admin', $roles, true),
                'is_super_admin' => in_array('super_admin', $roles, true),
            ],

            'portal_access' => [
                'member' => in_array('member', $roles, true),
                'association' => in_array('association_officer', $roles, true) && $activeAssociation !== null,
                'institution' => in_array('institution_user', $roles, true) && $institution !== null && $institutionLinkIsActive,
                'admin' => in_array('admin', $roles, true),
                'super_admin' => in_array('super_admin', $roles, true),
            ],

            'security' => [
                'email_verified' => $user->email_verified_at !== null,
                'requires_two_factor' => (bool) $user->requires_two_factor,
                'two_factor_confirmed' => $user->two_factor_confirmed_at !== null,
                'two_factor_confirmed_at' => optional($user->two_factor_confirmed_at)->toIso8601String(),
                'last_security_confirmation_at' => optional($user->last_security_confirmation_at)->toIso8601String(),
                'last_login_at' => optional($user->last_login_at)->toIso8601String(),
            ],

            'association_context' => $activeAssociation ? [
                'id' => $activeAssociation->id,
                'external_id' => $activeAssociation->external_id,
                'name' => $activeAssociation->name,
                'code' => $activeAssociation->code,
                'status' => $activeAssociation->status,
                'is_enabled' => (bool) $activeAssociation->is_enabled,
                'designation_title' => $activeAssociation->pivot?->designation_title,
            ] : null,

            'institution_context' => $institution ? [
                'id' => $institution->id,
                'external_id' => $institution->external_id,
                'name' => $institution->name,
                'licence_id' => $institution->licence_id,
                'onboarding_status' => $institution->onboarding_status,
                'account_status' => $institution->account_status,
                'governance_status' => $institution->governance_status,
                'primary_link_active' => $institutionLinkIsActive,
            ] : null,

            'member_profile' => $member ? new MemberProfileResource($member) : null,
            'member_application' => $memberApplication ? new MemberApplicationResource($memberApplication) : null,
            'institution_profile' => $institution ? new InstitutionProfileResource($institution) : null,

            'onboarding_status' => [
                'member_application_exists' => $memberApplication !== null,
                'member_application_status' => $memberApplicationStatus,
                'member_submission_stage' => $memberSubmissionStage,
                'member_profile_exists' => $member?->profile !== null,
                'member_approved' => $member !== null && $member->approval_status === 'approved',
                'member_can_edit_application' => in_array($memberApplicationStatus, ['draft', 'changes_requested'], true),

                'institution_profile_exists' => $institution !== null,
                'institution_onboarding_status' => $institutionOnboardingStatus,
                'institution_account_status' => $institutionAccountStatus,
                'institution_governance_status' => $institutionGovernanceStatus,
                'institution_is_fully_onboarded' => $institutionOnboardingStatus === 'completed',
                'institution_licensing_terms_acceptance_required' => $institutionLicensingTermsAcceptanceRequired,
            ],
        ];
    }
}
