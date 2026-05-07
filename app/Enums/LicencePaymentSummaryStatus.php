<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum LicencePaymentSummaryStatus: string
{
    use ProvidesEnumValues;

    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Waived = 'waived';
    case Failed = 'failed';
}
