<?php

namespace App\Actions\Licensing;

use App\Models\Institution;
use InvalidArgumentException;

class ResolveInstitutionLicensingBasisAction
{
    public function execute(Institution $institution): string
    {
        return match ($institution->institution_type) {
            'university', 'polytechnic', 'college_of_education', 'research_institute' => 'per_student',
            'professional_body' => 'per_member',
            'religious_organization' => 'per_branch',
            'corporate_organization', 'government_agency', 'ngo', 'library', 'other' => 'flat_rate',
            default => throw new InvalidArgumentException('Unsupported institution type for licensing basis resolution.'),
        };
    }
}
