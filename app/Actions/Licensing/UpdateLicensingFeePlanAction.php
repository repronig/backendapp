<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Models\LicensingFeePlan;
use App\Models\User;

class UpdateLicensingFeePlanAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(LicensingFeePlan $plan, array $data, ?User $actor = null, ?string $ipAddress = null, ?string $userAgent = null): LicensingFeePlan
    {
        $before = $plan->toArray();

        $plan->update([
            'institution_type' => $data['institution_type'] ?? $plan->institution_type,
            'basis_type' => $data['basis_type'] ?? $plan->basis_type,
            'unit_cost' => array_key_exists('unit_cost', $data) ? $data['unit_cost'] : $plan->unit_cost,
            'flat_amount' => array_key_exists('flat_amount', $data) ? $data['flat_amount'] : $plan->flat_amount,
            'effective_from_year' => $data['effective_from_year'] ?? $plan->effective_from_year,
            'effective_to_year' => $data['effective_to_year'] ?? $plan->effective_to_year,
            'is_active' => $data['is_active'] ?? $plan->is_active,
            'description' => array_key_exists('description', $data) ? $data['description'] : $plan->description,
            'metadata_json' => $data['metadata_json'] ?? $plan->metadata_json,
        ]);

        $fresh = $plan->fresh();

        $this->logAuditAction->execute(
            $actor,
            'licensing_fee_plan_updated',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
