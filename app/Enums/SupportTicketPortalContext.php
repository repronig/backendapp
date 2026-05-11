<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum SupportTicketPortalContext: string
{
    use ProvidesEnumValues;

    case Member = 'member';
    case Association = 'association';
    case Institution = 'institution';

    /** Path prefix for the SPA (no trailing slash). */
    public function frontendSupportPath(): string
    {
        return match ($this) {
            self::Member => '/member/support',
            self::Association => '/association/support',
            self::Institution => '/institution/support',
        };
    }
}
