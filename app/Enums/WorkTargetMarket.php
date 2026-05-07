<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkTargetMarket: string
{
    use ProvidesEnumValues;

    case School = 'school_market';
    case Tertiary = 'tertiary_education_market';
    case TradeBook = 'general_trade_book_market';
    case GeneralPublic = 'general_public';
    case Other = 'other';
}
