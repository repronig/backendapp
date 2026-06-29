<?php

namespace App\Support\MemberWorkImports;

use App\Enums\WorkFormat;
use App\Enums\WorkIdentifierType;
use App\Enums\WorkProductionStatus;
use App\Enums\WorkTargetMarket;
use App\Enums\WorkType;
use App\Models\Language;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MemberWorkImportCsv
{
    /** @return list<string> */
    public static function requiredColumns(): array
    {
        return [
            'type_of_work',
            'title',
            'primary_language',
            'work_format',
            'identifier_type',
            'identifier_value',
            'target_market',
            'production_status',
            'synopsis',
        ];
    }

    /** @return list<string> */
    public static function optionalColumns(): array
    {
        return [
            'subtitle',
            'publication_year',
            'doi',
            'publisher_name',
            'notes',
            'other_work_type',
            'target_market_other',
        ];
    }

    /** @return list<string> */
    public static function allColumns(): array
    {
        $requiredAfterTitle = array_slice(self::requiredColumns(), 2);
        $optionalWithoutSubtitle = array_values(array_filter(
            self::optionalColumns(),
            fn (string $column) => $column !== 'subtitle'
        ));

        return array_merge(
            ['type_of_work', 'title', 'subtitle'],
            $requiredAfterTitle,
            $optionalWithoutSubtitle
        );
    }

    public static function sanitizeIdentifierForFilename(string $value): string
    {
        $sanitized = preg_replace('/[^\w\-\.]/', '_', $value);

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : $value;
    }

    public static function zipLookupKey(string $identifierType, string $identifierValue): string
    {
        return strtolower(trim($identifierType)).':'.self::sanitizeIdentifierForFilename(trim($identifierValue));
    }

    /**
     * @param  iterable<int, \App\Models\MemberWorkImportItem>  $items
     */
    public static function findItemForZipFilenamePrefix(iterable $items, string $sanitizedPrefix): ?\App\Models\MemberWorkImportItem
    {
        $matches = [];

        foreach ($items as $item) {
            $payload = $item->row_payload_json ?? [];
            $identifierType = (string) ($payload['identifier_type'] ?? '');
            $identifierValue = (string) ($payload['identifier_value'] ?? '');

            if ($identifierValue === '') {
                continue;
            }

            if (self::sanitizeIdentifierForFilename($identifierValue) !== $sanitizedPrefix) {
                continue;
            }

            $matches[self::zipLookupKey($identifierType, $identifierValue)] = $item;
        }

        if ($matches === []) {
            return null;
        }

        return array_values($matches)[0];
    }

    /**
     * @return array{header: list<string>, rows: list<array<string, string|null>>}
     */
    public static function parse(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents)) ?: [];
        $parsed = array_map('str_getcsv', $lines);
        $header = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            array_shift($parsed) ?: []
        );

        $rows = [];
        foreach ($parsed as $row) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $payload = array_combine($header, array_pad($row, count($header), null)) ?: [];
            $normalized = [];
            foreach ($payload as $key => $value) {
                $normalized[$key] = is_string($value) ? trim($value) : (is_null($value) ? null : (string) $value);
                if ($normalized[$key] === '') {
                    $normalized[$key] = null;
                }
            }

            $rows[] = $normalized;
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, list<string>>
     */
    public static function validateRow(array $payload): array
    {
        $validator = Validator::make($payload, self::validationRules());

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }

    /** @return array<string, mixed> */
    public static function validationRules(): array
    {
        return [
            'type_of_work' => ['required', Rule::enum(WorkType::class)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'digits:4'],
            'synopsis' => ['required', 'string'],
            'primary_language' => ['required', 'string', 'max:80'],
            'work_format' => ['required', Rule::enum(WorkFormat::class)],
            'identifier_type' => ['required', Rule::enum(WorkIdentifierType::class)],
            'identifier_value' => ['required', 'string', 'max:120'],
            'doi' => ['nullable', 'string', 'max:255'],
            'publisher_name' => ['nullable', 'string', 'max:255'],
            'target_market' => ['required', Rule::enum(WorkTargetMarket::class)],
            'target_market_other' => ['nullable', 'string', 'max:180'],
            'production_status' => ['required', Rule::enum(WorkProductionStatus::class)],
            'other_work_type' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public static function isKnownLanguage(?string $languageName): bool
    {
        if (blank($languageName)) {
            return false;
        }

        return Language::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($languageName))])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function normalizeWorkPayload(array $payload): array
    {
        $normalized = [];
        foreach (self::allColumns() as $column) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            $value = $payload[$column];
            if ($column === 'publication_year' && $value !== null) {
                $normalized[$column] = (int) $value;

                continue;
            }

            $normalized[$column] = $value;
        }

        return $normalized;
    }

    public static function templateContents(): string
    {
        $columns = self::allColumns();
        $example = [
            'educational_non_fiction_scientific_text',
            'Introduction to Copyright Practice',
            'A practical guide',
            'English',
            'digital_copy',
            'isbn',
            '9783161484100',
            'school_market',
            'yes',
            'A useful educational text for rights management.',
            '2026',
            '',
            'REPRONIG Test Press',
            'Optional batch note',
            '',
            '',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $columns);
        fputcsv($handle, $example);
        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }
}
