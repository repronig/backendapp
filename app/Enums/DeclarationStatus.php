<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum DeclarationStatus: string
{
    use ProvidesEnumValues;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
