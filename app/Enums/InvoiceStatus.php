<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum InvoiceStatus: string
{
    use ProvidesEnumValues;

    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
