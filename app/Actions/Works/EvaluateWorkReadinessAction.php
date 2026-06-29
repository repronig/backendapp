<?php

namespace App\Actions\Works;

use App\Models\Work;

class EvaluateWorkReadinessAction
{
    /**
     * @return array{ready: bool, errors: array<string, list<string>>}
     */
    public function execute(Work $work): array
    {
        $work->loadMissing(['files', 'contributors']);
        $errors = [];

        $requiredFields = [
            'type_of_work',
            'title',
            'primary_language',
            'work_format',
            'identifier_type',
            'target_market',
            'production_status',
            'date_of_consent',
        ];

        foreach ($requiredFields as $field) {
            if (blank($work->{$field})) {
                $errors[$field][] = "The {$field} field is required before submission.";
            }
        }

        if (! (bool) $work->agreement_accepted) {
            $errors['agreement_accepted'][] = 'You must accept the rightsholder affiliation agreement before submission.';
        }

        if ($work->type_of_work === 'other_work_type' && blank($work->other_work_type)) {
            $errors['other_work_type'][] = 'Please specify the other work type before submission.';
        }

        if ($work->target_market === 'other' && blank($work->target_market_other)) {
            $errors['target_market_other'][] = 'Please specify the other target market before submission.';
        }

        $fileTypes = $work->files->pluck('file_type')->all();
        if (! in_array('cover_image', $fileTypes, true)) {
            $errors['files'][] = 'Cover image must be uploaded before submission.';
        }

        $ownershipTotal = (float) $work->contributors->sum('ownership_percentage');
        if (round($ownershipTotal, 2) !== 100.00) {
            $errors['ownership_percentage'][] = 'Contributor ownership percentages must total 100% before submission.';
        }

        if (Work::shouldEnforceIdentifierUniqueness($work->toArray())) {
            $duplicates = app(FindDuplicateWorksAction::class)->execute($work->toArray(), $work->id);
            if ($duplicates->isNotEmpty()) {
                $errors['identifier_value'][] = 'Potential duplicate work detected.';
            }
        }

        return [
            'ready' => $errors === [],
            'errors' => $errors,
        ];
    }
}
