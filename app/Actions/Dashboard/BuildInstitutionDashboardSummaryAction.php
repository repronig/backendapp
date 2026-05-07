<?php

namespace App\Actions\Dashboard;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Http\Resources\Api\V1\InstitutionProfileResource;
use App\Http\Resources\Api\V1\LicenceResource;
use App\Http\Resources\Api\V1\UsageDeclarationResource;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Licence;
use App\Models\UsageDeclaration;
use App\Models\User;
use Throwable;

class BuildInstitutionDashboardSummaryAction
{
    public function __construct(
        protected ResolveInstitutionForUserAction $resolveInstitutionForUserAction
    ) {}

    public function execute(User $user): ?array
    {
        if (! $user->hasRole('institution_user')) {
            return null;
        }

        try {
            $institution = $this->resolveInstitutionForUserAction
                ->execute($user)
                ->load(['profile', 'legacyDocuments']);
        } catch (Throwable $e) {
            return null;
        }

        $licencesQuery = Licence::query()->where('institution_id', $institution->id);
        $declarationsQuery = UsageDeclaration::query()->where('institution_id', $institution->id);
        $annualDeclarationsQuery = InstitutionAnnualDeclaration::query()->where('institution_id', $institution->id);

        $recentLicences = $licencesQuery->with(['declaration.faculties', 'payments'])->latest('licence_year')->limit(5)->get();
        $recentDeclarations = $declarationsQuery->latest('reporting_year')->limit(5)->get();
        $currentLicence = $licencesQuery->with(['declaration.faculties', 'payments'])->latest('licence_year')->first();

        return [
            'institution_profile' => new InstitutionProfileResource($institution),
            'stats' => [
                'total_licences' => (clone $licencesQuery)->count(),
                'active_licences' => (clone $licencesQuery)->where('licence_status', 'active')->count(),
                'pending_payment_licences' => (clone $licencesQuery)->where('licence_status', 'pending_payment')->count(),
                'total_annual_declarations' => (clone $annualDeclarationsQuery)->count(),
                'submitted_annual_declarations' => (clone $annualDeclarationsQuery)->where('declaration_status', 'submitted')->count(),
            ],
            'current_licence' => $currentLicence ? new LicenceResource($currentLicence) : null,
            'recent_licences' => LicenceResource::collection($recentLicences),
            'recent_usage_declarations' => UsageDeclarationResource::collection($recentDeclarations),
            'onboarding_status' => [
                'onboarding_status' => $institution->onboarding_status,
                'account_status' => $institution->account_status,
            ],
        ];
    }
}
