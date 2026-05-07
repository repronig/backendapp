<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkVerificationStatus: string
{
    use ProvidesEnumValues;

    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Disputed = 'disputed';
}
