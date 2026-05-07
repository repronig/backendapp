<?php

use App\Enums\MemberAuthorCategory;
use App\Enums\MemberAuthorType;
use App\Enums\WorkFormat;
use App\Enums\WorkIdentifierType;
use App\Enums\WorkProductionStatus;
use App\Enums\WorkTargetMarket;
use App\Enums\WorkType;

it('keeps member application enum contracts aligned with onboarding fields', function () {
    expect(MemberAuthorType::values())->toBe([
        'individual',
        'corporate',
        'agent',
    ]);

    expect(MemberAuthorCategory::values())->toBe([
        'author',
        'journalist',
        'photographer',
        'illustrator',
        'carver',
        'painter',
        'sculptor',
        'other',
    ]);
});

it('keeps work enum contracts aligned with the Add Work form', function () {
    expect(WorkType::values())->toBe([
        'educational_non_fiction_scientific_text',
        'fiction_text',
        'news_articles_journalistic_text',
        'book_content_visual_arts',
        'standalone_visual_works',
        'newspaper_magazines_inserts',
        'song_text',
        'musical_score',
        'other_work_type',
    ]);

    expect(WorkIdentifierType::values())->toBe([
        'isbn',
        'issn',
        'isni',
        'iswc',
        'url',
        'other',
    ]);

    expect(WorkFormat::values())->toBe([
        'digital_copy',
        'hard_copy',
        'hard_digital_copy',
        'audio',
        'video',
        'other',
    ]);

    expect(WorkProductionStatus::values())->toBe(['yes', 'no']);

    expect(WorkTargetMarket::values())->toBe([
        'school_market',
        'tertiary_education_market',
        'general_trade_book_market',
        'general_public',
        'other',
    ]);
});
