<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Models\User;
use App\Models\Work;
use Illuminate\Validation\ValidationException;

class UpdateWorkAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected FindDuplicateWorksAction $findDuplicateWorksAction,
    ) {}

    public function execute(
        Work $work,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Work {
        $canUpdateDraft = $work->work_status === WorkStatus::Draft;
        $canUpdateChangesRequested = $work->work_status === WorkStatus::ChangesRequested;
        $canUpdateApprovedAfterRequest = $work->work_status === WorkStatus::Approved && $work->update_request_status === 'approved';

        if (! $canUpdateDraft && ! $canUpdateChangesRequested && ! $canUpdateApprovedAfterRequest) {
            throw ValidationException::withMessages([
                'work_status' => ['Only draft/changes-requested works (or approved works with approved update request) can be modified.'],
            ]);
        }

        $before = $work->toArray();

        $probe = [
            'identifier_type' => $data['identifier_type'] ?? $work->identifier_type,
            'identifier_value' => array_key_exists('identifier_value', $data)
                ? $data['identifier_value']
                : $work->identifier_value,
            'title' => $data['title'] ?? $work->title,
            'publication_year' => $data['publication_year'] ?? $work->publication_year,
            'publisher_name' => $data['publisher_name'] ?? $work->publisher_name,
        ];

        if (Work::shouldEnforceIdentifierUniqueness($probe)) {
            $identifierType = strtolower((string) ($probe['identifier_type'] ?? ''));
            $duplicates = $this->findDuplicateWorksAction->execute($probe, $work->id);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'identifier_value' => ['A work with this '.$identifierType.' identifier already exists.'],
                ]);
            }
        }

        $work->update($data);

        // After a granted update request is used, require member to re-submit for review.
        if ($canUpdateApprovedAfterRequest) {
            $work->forceFill([
                'work_status' => WorkStatus::Draft->value,
                'update_request_status' => null,
                'update_requested_at' => null,
                'update_requested_by_member_id' => null,
                'update_request_note' => null,
                'update_request_reviewed_at' => null,
                'update_request_reviewed_by_user_id' => null,
                'update_request_review_note' => null,
            ])->save();
        }

        $fresh = $work->fresh(['contributors.disputedBy', 'files', 'member', 'verifier', 'lastReviewer']);

        $this->logAuditAction->execute(
            $actor,
            'work_updated',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
