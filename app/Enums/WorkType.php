<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum WorkType: string
{
    use ProvidesEnumValues;

    case EducationalNonFictionScientific = 'educational_non_fiction_scientific_text';
    case FictionText = 'fiction_text';
    case NewsArticlesJournalisticText = 'news_articles_journalistic_text';
    case BookContentVisualArts = 'book_content_visual_arts';
    case StandaloneVisualWorks = 'standalone_visual_works';
    case NewspaperMagazinesInserts = 'newspaper_magazines_inserts';
    case SongText = 'song_text';
    case MusicalScore = 'musical_score';
    case Other = 'other_work_type';
}
