<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum MemberAuthorCategory: string
{
    use ProvidesEnumValues;

    case Author = 'author';
    case Journalist = 'journalist';
    case Photographer = 'photographer';
    case Illustrator = 'illustrator';
    case Carver = 'carver';
    case Painter = 'painter';
    case Sculptor = 'sculptor';
    case Other = 'other';
}
