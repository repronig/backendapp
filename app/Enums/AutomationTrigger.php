<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum AutomationTrigger: string
{
    use ProvidesEnumValues;

    case Schedule = 'schedule';
    case Event = 'event';
}
