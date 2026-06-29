<?php

use App\Models\MemberWorkImportItem;
use App\Support\MemberWorkImports\MemberWorkImportCsv;

it('builds composite zip lookup keys from identifier type and value', function () {
    expect(MemberWorkImportCsv::zipLookupKey('isbn', '9783161484100'))
        ->toBe('isbn:9783161484100')
        ->and(MemberWorkImportCsv::zipLookupKey('ISSN', '1234-5678'))
        ->toBe('issn:1234-5678');
});

it('finds import items by sanitized identifier prefix', function () {
    $isbnItem = new MemberWorkImportItem([
        'row_payload_json' => [
            'identifier_type' => 'isbn',
            'identifier_value' => '9783161484100',
        ],
    ]);
    $issnItem = new MemberWorkImportItem([
        'row_payload_json' => [
            'identifier_type' => 'issn',
            'identifier_value' => '9783161484100',
        ],
    ]);

    $match = MemberWorkImportCsv::findItemForZipFilenamePrefix(
        [$isbnItem, $issnItem],
        '9783161484100'
    );

    expect($match)->not->toBeNull()
        ->and($match?->row_payload_json['identifier_type'])->toBe('isbn');
});

it('returns null when no import item matches the zip prefix', function () {
    $item = new MemberWorkImportItem([
        'row_payload_json' => [
            'identifier_type' => 'isbn',
            'identifier_value' => '9783161484101',
        ],
    ]);

    expect(MemberWorkImportCsv::findItemForZipFilenamePrefix([$item], '9783161484100'))->toBeNull();
});

it('sanitizes identifier values for zip filename matching', function () {
    expect(MemberWorkImportCsv::sanitizeIdentifierForFilename('978-3161-484100'))
        ->toBe('978-3161-484100');
});

it('includes a single example row in the import template', function () {
    $parsed = MemberWorkImportCsv::parse(MemberWorkImportCsv::templateContents());

    expect(count($parsed['rows']))->toBe(1)
        ->and($parsed['rows'][0]['title'])->toBe('Introduction to Copyright Practice')
        ->and($parsed['rows'][0]['subtitle'])->toBe('A practical guide');
});

it('orders subtitle immediately after title in template columns', function () {
    $columns = MemberWorkImportCsv::allColumns();
    $titleIndex = array_search('title', $columns, true);

    expect($titleIndex)->not->toBeFalse()
        ->and($columns[$titleIndex + 1] ?? null)->toBe('subtitle');
});
