<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum ComplianceOverallStatus: string
{
    use ProvidesEnumValues;

    case Ok = 'ok';
    case Attention = 'attention';
    case Critical = 'critical';
}
