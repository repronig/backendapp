<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionLicensingReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'institution_name' => $this->name,
            'licence_id' => $this->licence_id,
            'address' => collect([$this->address_line_1, $this->city, $this->state, $this->country])
                ->filter()
                ->implode(', '),
            'institution_type' => $this->institution_type,
            'licensing_year' => $this->latestAnnualDeclaration?->licensing_year,
            'expected_amount' => $this->latestAnnualDeclaration?->expected_amount,
            'amount_paid' => $this->latestAnnualDeclaration?->paid_amount,
            'outstanding_amount' => $this->latestAnnualDeclaration?->outstanding_amount,
            'payment_status' => $this->licences->first()?->payment_status,
        ];
    }
}
