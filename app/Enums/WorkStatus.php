<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkStatus: string
{
    use ProvidesEnumValues;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ChangesRequested = 'changes_requested';
    case Verified = 'verified';
    case Disputed = 'disputed';
    case Approved = 'approved';
    case Restricted = 'restricted';
}
