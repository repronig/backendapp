<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum RoyaltyPaymentMode: string
{
    use ProvidesEnumValues;

    case Bank = 'bank';
    case Cheque = 'cheque';
    case MobileMoney = 'mobile_money';
}
