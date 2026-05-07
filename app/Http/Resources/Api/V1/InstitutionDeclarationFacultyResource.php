<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionDeclarationFacultyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'faculty_name' => $this->faculty_name,
            'student_count' => $this->student_count,
            'sort_order' => $this->sort_order,
        ];
    }
}
