<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\WorkFormat;
use App\Enums\WorkIdentifierType;
use App\Enums\WorkProductionStatus;
use App\Enums\WorkTargetMarket;
use App\Enums\WorkType;
use Illuminate\Validation\Rule;

trait WorkFieldRules
{
    protected function workFieldRules(string $presence = 'sometimes'): array
    {
        return [
            'type_of_work' => [$presence, Rule::enum(WorkType::class)],
            'title' => [$presence, 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'digits:4'],
            'synopsis' => ['nullable', 'string'],
            'primary_language' => ['nullable', 'string', 'max:80'],
            'work_format' => ['nullable', Rule::enum(WorkFormat::class)],
            'identifier_type' => ['nullable', Rule::enum(WorkIdentifierType::class)],
            'identifier_value' => ['nullable', 'string', 'max:120'],
            'doi' => ['nullable', 'string', 'max:255'],
            'publisher_name' => ['nullable', 'string', 'max:255'],
            'target_market' => ['nullable', Rule::enum(WorkTargetMarket::class)],
            'target_market_other' => ['nullable', 'string', 'max:180'],
            'production_status' => ['nullable', Rule::enum(WorkProductionStatus::class)],
            'agreement_accepted' => ['nullable', 'boolean'],
            'date_of_consent' => ['nullable', 'date'],
            'other_work_type' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
