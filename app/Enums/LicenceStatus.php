<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum LicenceStatus: string
{
    use ProvidesEnumValues;

    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
