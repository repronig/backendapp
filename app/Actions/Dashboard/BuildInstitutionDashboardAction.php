<?php

namespace App\Actions\Dashboard;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Http\Resources\Api\V1\InstitutionAnnualDeclarationResource;
use App\Http\Resources\Api\V1\InstitutionProfileResource;
use App\Http\Resources\Api\V1\LicenceResource;
use App\Http\Resources\Api\V1\UsageDeclarationResource;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\InstitutionDocument;
use App\Models\Invoice;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\UsageDeclaration;
use App\Models\User;
use App\Support\DashboardPayload;

class BuildInstitutionDashboardAction
{
    public function __construct(
        protected ResolveInstitutionForUserAction $resolveInstitutionForUserAction
    ) {}

    public function execute(User $user): array
    {
        $institution = $this->resolveInstitutionForUserAction
            ->execute($user)
            ->load(['profile', 'legacyDocuments']);

        $licencesQuery = Licence::query()->where('institution_id', $institution->id);
        $declarationsQuery = UsageDeclaration::query()->where('institution_id', $institution->id);
        $annualDeclarationsQuery = InstitutionAnnualDeclaration::query()->where('institution_id', $institution->id);
        $paymentsQuery = LicencePayment::query()->where('institution_id', $institution->id);

        $licenceIds = Licence::query()->where('institution_id', $institution->id)->pluck('id');
        $usageDeclarationIds = UsageDeclaration::query()->where('institution_id', $institution->id)->pluck('id');
        $annualDeclarationIds = InstitutionAnnualDeclaration::query()->where('institution_id', $institution->id)->pluck('id');
        $invoiceIds = Invoice::query()->where('institution_id', $institution->id)->pluck('id');
        $paymentIds = LicencePayment::query()->where('institution_id', $institution->id)->pluck('id');
        $documentIds = InstitutionDocument::query()->where('institution_id', $institution->id)->pluck('id');

        $recentActivity = AuditLog::query()
            ->with('actor')
            ->where(function ($query) use ($institution, $licenceIds, $usageDeclarationIds, $annualDeclarationIds, $invoiceIds, $paymentIds, $documentIds) {
                $query->where(function ($nested) use ($institution) {
                    $nested->where('subject_type', Institution::class)
                        ->where('subject_id', $institution->id);
                });

                if ($licenceIds->isNotEmpty()) {
                    $query->orWhere(fn ($nested) => $nested->where('subject_type', Licence::class)->whereIn('subject_id', $licenceIds->all()));
                }

                if ($usageDeclarationIds->isNotEmpty()) {
                    $query->orWhere(fn ($nested) => $nested->where('subject_type', UsageDeclaration::class)->whereIn('subject_id', $usageDeclarationIds->all()));
                }

                if ($annualDeclarationIds->isNotEmpty()) {
                    $query->orWhere(fn ($nested) => $nested->where('subject_type', InstitutionAnnualDeclaration::class)->whereIn('subject_id', $annualDeclarationIds->all()));
                }

                if ($invoiceIds->isNotEmpty()) {
                    $query->orWhere(fn ($nested) => $nested->where('subject_type', Invoice::class)->whereIn('subject_id', $invoiceIds->all()));
                }

                if ($paymentIds->isNotEmpty()) {
                    $query->orWhere(fn ($nested) => $nested->where('subject_type', LicencePayment::class)->whereIn('subject_id', $paymentIds->all()));
                }

                if ($documentIds->isNotEmpty()) {
                    $query->orWhere(fn ($nested) => $nested->where('subject_type', InstitutionDocument::class)->whereIn('subject_id', $documentIds->all()));
                }
            })
            ->latest('created_at')
            ->limit(DashboardPayload::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(fn (AuditLog $log) => DashboardPayload::serializeAuditLog($log))
            ->values()
            ->all();

        return [
            'meta' => DashboardPayload::meta(),
            'institution' => new InstitutionProfileResource($institution),
            'stats' => [
                'total_licences' => (clone $licencesQuery)->count(),
                'active_licences' => (clone $licencesQuery)->where('licence_status', 'active')->count(),
                'pending_payment_licences' => (clone $licencesQuery)->where('licence_status', 'pending_payment')->count(),
                'total_annual_declarations' => (clone $annualDeclarationsQuery)->count(),
                'submitted_annual_declarations' => (clone $annualDeclarationsQuery)->where('declaration_status', 'submitted')->count(),
                'paid_payments' => (clone $paymentsQuery)->where('payment_status', 'paid')->count(),
                'total_paid_amount' => (float) (clone $paymentsQuery)->where('payment_status', 'paid')->sum('amount_allocated'),
            ],
            'current_licence' => ($current = $licencesQuery->with(['declaration.faculties', 'payments'])->latest('licence_year')->first())
                ? new LicenceResource($current)
                : null,
            'recent_licences' => LicenceResource::collection(
                $licencesQuery->with(['declaration.faculties', 'payments'])->latest('licence_year')->limit(10)->get()
            ),
            'recent_usage_declarations' => UsageDeclarationResource::collection(
                $declarationsQuery->latest('reporting_year')->limit(10)->get()
            ),
            'recent_annual_declarations' => InstitutionAnnualDeclarationResource::collection(
                InstitutionAnnualDeclaration::query()
                    ->where('institution_id', $institution->id)
                    ->with('invoice')
                    ->latest('licensing_year')
                    ->limit(10)
                    ->get()
            ),
            'recent_activity' => $recentActivity,
            'onboarding_status' => [
                'onboarding_status' => $institution->onboarding_status,
                'account_status' => $institution->account_status,
            ],
        ];
    }
}
