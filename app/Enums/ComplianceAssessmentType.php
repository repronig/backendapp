<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum ComplianceAssessmentType: string
{
    use ProvidesEnumValues;

    case Scheduled = 'scheduled';
    case Manual = 'manual';
    case DeclarationLinked = 'declaration_linked';
}
