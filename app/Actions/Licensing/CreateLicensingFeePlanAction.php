<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Models\LicensingFeePlan;
use App\Models\User;

class CreateLicensingFeePlanAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(array $data, ?User $actor = null, ?string $ipAddress = null, ?string $userAgent = null): LicensingFeePlan
    {
        $plan = LicensingFeePlan::create([
            'institution_type' => $data['institution_type'],
            'basis_type' => $data['basis_type'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'flat_amount' => $data['flat_amount'] ?? null,
            'effective_from_year' => $data['effective_from_year'],
            'effective_to_year' => $data['effective_to_year'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'description' => $data['description'] ?? null,
            'metadata_json' => $data['metadata_json'] ?? null,
        ]);

        $this->logAuditAction->execute(
            $actor,
            'licensing_fee_plan_created',
            $plan,
            null,
            $plan->toArray(),
            $ipAddress,
            $userAgent
        );

        return $plan;
    }
}
