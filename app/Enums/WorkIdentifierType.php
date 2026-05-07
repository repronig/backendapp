<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkIdentifierType: string
{
    use ProvidesEnumValues;

    case Isbn = 'isbn';
    case Issn = 'issn';
    case Isni = 'isni';
    case Iswc = 'iswc';
    case Url = 'url';
    case Other = 'other';
}
