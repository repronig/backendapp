<?php

namespace App\Support\MemberWorkImports;

use App\Enums\WorkFormat;
use App\Enums\WorkIdentifierType;
use App\Enums\WorkProductionStatus;
use App\Enums\WorkTargetMarket;
use App\Enums\WorkType;
use App\Models\Language;

final class MemberWorkImportColumnReference
{
    /**
     * @return list<array{column: string, required: bool, note: string}>
     */
    public static function orderedRows(): array
    {
        return [
            ['column' => 'type_of_work', 'required' => true, 'note' => self::allowedValuesNote(WorkType::values())],
            ['column' => 'title', 'required' => true, 'note' => 'Work title.'],
            ['column' => 'subtitle', 'required' => false, 'note' => 'Optional subtitle.'],
            ['column' => 'primary_language', 'required' => true, 'note' => self::primaryLanguageNote()],
            ['column' => 'work_format', 'required' => true, 'note' => self::allowedValuesNote(WorkFormat::values())],
            ['column' => 'identifier_type', 'required' => true, 'note' => self::allowedValuesNote(WorkIdentifierType::values())],
            ['column' => 'identifier_value', 'required' => true, 'note' => 'Unique identifier used for duplicate checks and ZIP file mapping.'],
            ['column' => 'target_market', 'required' => true, 'note' => self::allowedValuesNote(WorkTargetMarket::values())],
            ['column' => 'production_status', 'required' => true, 'note' => self::allowedValuesNote(WorkProductionStatus::values())],
            ['column' => 'synopsis', 'required' => true, 'note' => 'Short description of the work.'],
            ['column' => 'publication_year', 'required' => false, 'note' => 'Four-digit year.'],
            ['column' => 'doi', 'required' => false, 'note' => 'Digital object identifier.'],
            ['column' => 'publisher_name', 'required' => false, 'note' => 'Publisher name if applicable.'],
            ['column' => 'notes', 'required' => false, 'note' => 'Internal notes.'],
            ['column' => 'other_work_type', 'required' => false, 'note' => 'Required when type_of_work is other_work_type.'],
            ['column' => 'target_market_other', 'required' => false, 'note' => 'Required when target_market is other.'],
        ];
    }

    /**
     * @return list<array{pattern: string, note: string}>
     */
    public static function zipFilePatterns(): array
    {
        return [
            ['pattern' => '{identifier_value}_cover.{jpg|jpeg|png|webp}', 'note' => 'Cover image (required before submit).'],
            ['pattern' => '{identifier_value}_copyright.pdf', 'note' => 'Optional copyright page.'],
            ['pattern' => '{identifier_value}_proof.pdf', 'note' => 'Optional proof of ownership.'],
        ];
    }

    /**
     * @param  list<string>  $values
     */
    private static function allowedValuesNote(array $values): string
    {
        return 'Allowed values: '.implode(', ', $values).'.';
    }

    private static function primaryLanguageNote(): string
    {
        $languageNames = Language::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($languageNames === []) {
            return 'Allowed values: English (no other active languages are configured).';
        }

        return self::allowedValuesNote($languageNames);
    }
}
