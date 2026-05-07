<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum IntegrationProvider: string
{
    use ProvidesEnumValues;

    case WipoConnect = 'wipo_connect';
}
