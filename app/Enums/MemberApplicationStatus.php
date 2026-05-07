<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum MemberApplicationStatus: string
{
    use ProvidesEnumValues;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';
}
