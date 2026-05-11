<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum SupportTicketStatus: string
{
    use ProvidesEnumValues;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
