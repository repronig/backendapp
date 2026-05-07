<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageDeclarationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'licence_id' => $this->licence_id,
            'institution_id' => $this->institution_id,
            'reporting_year' => $this->reporting_year,
            'declaration_status' => $this->declaration_status,
            'declared_student_population' => $this->declared_student_population,
            'declared_academic_staff_count' => $this->declared_academic_staff_count,
            'declared_administrative_staff_count' => $this->declared_administrative_staff_count,
            'declared_campuses_count' => $this->declared_campuses_count,
            'declared_library_capacity' => $this->declared_library_capacity,
            'declaration_notes' => $this->declaration_notes,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}