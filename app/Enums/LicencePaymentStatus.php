<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum LicencePaymentStatus: string
{
    use ProvidesEnumValues;

    case Pending = 'pending';
    case PendingOffline = 'pending_offline';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
