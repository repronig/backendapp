<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Jobs\SendWorkSubmittedAdminNotificationsJob;
use App\Models\User;
use App\Models\Work;
use Illuminate\Validation\ValidationException;

class SubmitWorkAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected FindDuplicateWorksAction $findDuplicateWorksAction,
    ) {}

    public function execute(
        Work $work,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Work {
        if (! in_array($work->work_status, [WorkStatus::Draft, WorkStatus::ChangesRequested], true)) {
            throw ValidationException::withMessages([
                'work_status' => ['Only draft or changes-requested works can be submitted.'],
            ]);
        }

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
                throw ValidationException::withMessages([
                    $field => ["The {$field} field is required before submission."],
                ]);
            }
        }

        if (! (bool) $work->agreement_accepted) {
            throw ValidationException::withMessages([
                'agreement_accepted' => ['You must accept the rightsholder affiliation agreement before submission.'],
            ]);
        }

        if ($work->type_of_work === 'other_work_type' && blank($work->other_work_type)) {
            throw ValidationException::withMessages([
                'other_work_type' => ['Please specify the other work type before submission.'],
            ]);
        }

        if ($work->target_market === 'other' && blank($work->target_market_other)) {
            throw ValidationException::withMessages([
                'target_market_other' => ['Please specify the other target market before submission.'],
            ]);
        }

        $fileTypes = $work->files()->pluck('file_type')->all();

        if (! in_array('cover_image', $fileTypes, true)) {
            throw ValidationException::withMessages([
                'files' => ['Cover image must be uploaded before submission.'],
            ]);
        }

        $ownershipTotal = (float) $work->contributors()->sum('ownership_percentage');

        if (round($ownershipTotal, 2) !== 100.00) {
            throw ValidationException::withMessages([
                'ownership_percentage' => [
                    'Contributor ownership percentages must total 100% before submission.',
                ],
            ]);
        }

        if (Work::shouldEnforceIdentifierUniqueness($work->toArray())) {
            $duplicates = $this->findDuplicateWorksAction->execute($work->toArray(), $work->id);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'identifier_value' => ['Potential duplicate work detected. An admin review will be required before submission can proceed.'],
                ]);
            }
        }

        $before = $work->toArray();

        $work->update([
            'work_status' => WorkStatus::Submitted->value,
            'verification_status' => WorkVerificationStatus::Pending->value,
            'submitted_at' => now(),
            'is_disputed' => false,
        ]);

        $fresh = $work->fresh([
            'contributors.disputedBy',
            'files',
            'member',
            'reviews',
            'verifier',
            'lastReviewer',
        ]);

        $this->logAuditAction->execute(
            $actor,
            'work_submitted',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        SendWorkSubmittedAdminNotificationsJob::dispatch((int) $fresh->id)->afterCommit();

        return $fresh;
    }
}
