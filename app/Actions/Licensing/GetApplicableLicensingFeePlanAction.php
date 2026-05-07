<?php

namespace App\Actions\Licensing;

use App\Models\LicensingFeePlan;

class GetApplicableLicensingFeePlanAction
{
    public function execute(string $institutionType, int $licensingYear): ?LicensingFeePlan
    {
        return LicensingFeePlan::query()
            ->where('institution_type', $institutionType)
            ->where('is_active', true)
            ->where('effective_from_year', '<=', $licensingYear)
            ->where(function ($query) use ($licensingYear) {
                $query->whereNull('effective_to_year')
                    ->orWhere('effective_to_year', '>=', $licensingYear);
            })
            ->orderByDesc('effective_from_year')
            ->latest('id')
            ->first();
    }
}
