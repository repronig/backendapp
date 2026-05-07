<?php

namespace App\Actions\Licensing;

use App\Models\Institution;

class GenerateInstitutionLicenceIdAction
{
    public function execute(Institution $institution): string
    {
        if ($institution->licence_id) {
            return $institution->licence_id;
        }

        $prefix = match ($institution->institution_type) {
            'university' => 'UNI',
            'polytechnic' => 'POLY',
            'college_of_education' => 'COE',
            'professional_body' => 'PRO',
            'religious_organization' => 'REL',
            'corporate_organization' => 'CORP',
            'government_agency' => 'GOV',
            'ngo' => 'NGO',
            'research_institute' => 'RES',
            'library' => 'LIB',
            default => 'OTH',
        };

        return sprintf('RI-%s-%06d', $prefix, $institution->id);
    }
}
