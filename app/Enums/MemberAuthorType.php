<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum MemberAuthorType: string
{
    use ProvidesEnumValues;

    case Individual = 'individual';
    case Corporate = 'corporate';
    case Agent = 'agent';
}
