<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum IntegrationSyncStatus: string
{
    use ProvidesEnumValues;

    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
