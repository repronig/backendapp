<?php

namespace App\Actions\Licensing;

use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;

class BuildInstitutionLicensingSummaryAction
{
    public function execute(Institution $institution, ?int $licensingYear = null): array
    {
        $declaration = $this->resolveDeclaration($institution, $licensingYear);
        $licence = $declaration?->licence;

        return [
            'institution_id' => $institution->id,
            'institution_name' => $institution->name,
            'licence_id' => $institution->licence_id,
            'institution_type' => $institution->institution_type,
            'address' => collect([$institution->address_line_1, $institution->city, $institution->state, $institution->country])->filter()->implode(', '),
            'licensing_year' => $declaration?->licensing_year,
            'basis_type' => $declaration?->basis_type,
            'faculties_count' => $declaration?->declared_faculties_count,
            'declared_units' => $declaration?->declared_units,
            'declared_students_count' => $declaration?->declared_students_count,
            'declared_members_count' => $declaration?->declared_members_count,
            'declared_branches_count' => $declaration?->declared_branches_count,
            'pricing_unit_cost' => $declaration?->pricing_unit_cost,
            'pricing_flat_amount' => $declaration?->pricing_flat_amount,
            'expected_amount' => $declaration?->expected_amount,
            'amount_paid' => $declaration?->paid_amount,
            'outstanding_amount' => $declaration?->outstanding_amount,
            'payment_status' => $licence?->payment_status,
            'licence_status' => $licence?->licence_status,
            'faculties' => $declaration?->faculties?->all() ?? [],
        ];
    }

    protected function resolveDeclaration(Institution $institution, ?int $licensingYear = null): ?InstitutionAnnualDeclaration
    {
        return $institution->annualDeclarations()
            ->with(['faculties', 'licence'])
            ->when($licensingYear !== null, fn ($query) => $query->where('licensing_year', $licensingYear))
            ->orderByDesc('licensing_year')
            ->first();
    }
}
