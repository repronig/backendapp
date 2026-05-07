<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkProductionStatus: string
{
    use ProvidesEnumValues;

    case Yes = 'yes';
    case No = 'no';
}
