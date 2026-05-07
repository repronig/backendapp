<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionLicensingSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'institution_id' => $this['institution_id'] ?? null,
            'institution_name' => $this['institution_name'] ?? null,
            'licence_id' => $this['licence_id'] ?? null,
            'institution_type' => $this['institution_type'] ?? null,
            'address' => $this['address'] ?? null,
            'licensing_year' => $this['licensing_year'] ?? null,
            'basis_type' => $this['basis_type'] ?? null,
            'faculties_count' => $this['faculties_count'] ?? null,
            'declared_units' => $this['declared_units'] ?? null,
            'declared_students_count' => $this['declared_students_count'] ?? null,
            'declared_members_count' => $this['declared_members_count'] ?? null,
            'declared_branches_count' => $this['declared_branches_count'] ?? null,
            'pricing_unit_cost' => $this['pricing_unit_cost'] ?? null,
            'pricing_flat_amount' => $this['pricing_flat_amount'] ?? null,
            'expected_amount' => $this['expected_amount'] ?? null,
            'amount_paid' => $this['amount_paid'] ?? null,
            'outstanding_amount' => $this['outstanding_amount'] ?? null,
            'payment_status' => $this['payment_status'] ?? null,
            'licence_status' => $this['licence_status'] ?? null,
            'faculties' => InstitutionDeclarationFacultyResource::collection(collect($this['faculties'] ?? [])),
        ];
    }
}
