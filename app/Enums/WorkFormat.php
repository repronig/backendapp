<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkFormat: string
{
    use ProvidesEnumValues;

    case DigitalCopy = 'digital_copy';
    case HardCopy = 'hard_copy';
    case DigitalAndHardCopy = 'hard_digital_copy';
    case Audio = 'audio';
    case Video = 'video';
    case Other = 'other';
}
