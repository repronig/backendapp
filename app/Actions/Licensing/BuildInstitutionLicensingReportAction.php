<?php

namespace App\Actions\Licensing;

use App\Models\Institution;
use App\Support\PostgresSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildInstitutionLicensingReportAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $licensingYear = isset($filters['licensing_year']) ? (int) $filters['licensing_year'] : null;

        return Institution::query()
            ->with([
                'latestAnnualDeclaration.faculties',
                'licences' => function ($query) use ($licensingYear) {
                    $query->when($licensingYear, fn ($sub) => $sub->where('licence_year', $licensingYear))
                        ->latest('licence_year')
                        ->latest('id')
                        ->limit(1);
                },
            ])
            ->when(
                ! empty($filters['institution_type']),
                fn ($query) => $query->where('institution_type', $filters['institution_type'])
            )
            ->when(
                $licensingYear,
                fn ($query) => $query->whereHas('annualDeclarations', fn ($sub) => $sub->where('licensing_year', $licensingYear))
            )
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($sub) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($sub, ['name', 'licence_id', 'email'], $search);
                    });
                }
            )
            ->when(
                ! empty($filters['outstanding_only']),
                fn ($query) => $query->whereHas('annualDeclarations', function ($sub) use ($licensingYear) {
                    $sub->when($licensingYear, fn ($inner) => $inner->where('licensing_year', $licensingYear))
                        ->where('outstanding_amount', '>', 0);
                })
            )
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
