<?php

namespace App\Actions\Licensing;

use App\Models\Institution;
use App\Models\LicensingFeePlan;
use Illuminate\Validation\ValidationException;

class CalculateInstitutionDeclarationExpectedAmountAction
{
    public function __construct(
        protected GetApplicableLicensingFeePlanAction $getApplicableLicensingFeePlanAction,
        protected ResolveInstitutionLicensingBasisAction $resolveInstitutionLicensingBasisAction
    ) {
    }

    public function execute(Institution $institution, array $declarationData, int $licensingYear): array
    {
        $plan = $this->getApplicableLicensingFeePlanAction->execute($institution->institution_type, $licensingYear)
            ?: $this->fallbackPlan($institution->institution_type);

        $basisType = $plan?->basis_type ?: $this->resolveInstitutionLicensingBasisAction->execute($institution);

        $facultyStudentTotal = collect($declarationData['faculties'] ?? [])
            ->sum(fn (array $faculty): int => (int) ($faculty['student_count'] ?? 0));

        $declaredStudents = (int) ($declarationData['declared_students_count'] ?? $facultyStudentTotal);
        $declaredMembers = (int) ($declarationData['declared_members_count'] ?? 0);
        $declaredBranches = (int) ($declarationData['declared_branches_count'] ?? 0);

        $declaredUnits = match ($basisType) {
            'per_student' => $declaredStudents,
            'per_member' => $declaredMembers,
            'per_branch' => $declaredBranches,
            'flat_rate' => 1,
            default => 0,
        };

        $unitCost = $plan?->unit_cost;
        $flatAmount = $plan?->flat_amount;

        if (in_array($basisType, ['per_student', 'per_member', 'per_branch'], true) && $plan && $unitCost === null) {
            throw ValidationException::withMessages([
                'fee_plan' => ['The applicable fee plan is missing a unit cost.'],
            ]);
        }

        if ($basisType === 'flat_rate' && $plan && $flatAmount === null) {
            throw ValidationException::withMessages([
                'fee_plan' => ['The applicable fee plan is missing a flat amount.'],
            ]);
        }

        $expectedAmount = match ($basisType) {
            'per_student', 'per_member', 'per_branch' => round(((float) $unitCost) * $declaredUnits, 2),
            'flat_rate' => round((float) $flatAmount, 2),
            default => 0.0,
        };

        return [
            'basis_type' => $basisType,
            'declared_units' => $declaredUnits,
            'pricing_unit_cost' => $unitCost,
            'pricing_flat_amount' => $flatAmount,
            'expected_amount' => $expectedAmount,
            'outstanding_amount' => max($expectedAmount, 0),
            'plan' => $plan,
        ];
    }

    protected function fallbackPlan(string $institutionType): ?LicensingFeePlan
    {
        return LicensingFeePlan::query()
            ->where('institution_type', $institutionType)
            ->where('is_active', true)
            ->orderByDesc('effective_from_year')
            ->latest('id')
            ->first();
    }
}
