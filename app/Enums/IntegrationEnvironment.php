<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum IntegrationEnvironment: string
{
    use ProvidesEnumValues;

    case Sandbox = 'sandbox';
    case Production = 'production';
}
