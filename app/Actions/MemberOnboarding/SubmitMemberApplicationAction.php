<?php

namespace App\Actions\MemberOnboarding;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Jobs\SendMemberApplicationSubmittedAssociationNotificationsJob;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        MemberApplication $memberApplication,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplication {
        $documents = $memberApplication->documents()->pluck('document_type')->all();
        $requiredDocumentTypes = [
            'proof_of_id',
            'proof_of_address',
        ];

        $missingDocumentTypes = array_values(array_diff($requiredDocumentTypes, $documents));

        if ($missingDocumentTypes !== []) {
            throw ValidationException::withMessages([
                'documents' => [
                    'Upload all required application documents before submitting your member application: '.implode(', ', $missingDocumentTypes).'.',
                ],
            ]);
        }

        if (! $memberApplication->consent_accepted || ! $memberApplication->consent_date) {
            throw ValidationException::withMessages([
                'consent_accepted' => ['Consent/mandate acceptance and consent date are required before submission.'],
            ]);
        }

        if (! in_array($memberApplication->application_status, [MemberApplicationStatus::Draft->value, MemberApplicationStatus::ChangesRequested->value], true)) {
            throw ValidationException::withMessages([
                'application_status' => ['Only draft or changes-requested applications can be submitted.'],
            ]);
        }

        $memberApplication->loadMissing('user');

        if (blank($memberApplication->user?->phone)) {
            throw ValidationException::withMessages([
                'phone' => ['Phone number is required before submitting your mandate.'],
            ]);
        }

        $before = $memberApplication->toArray();

        $memberApplication->update([
            'application_status' => MemberApplicationStatus::Submitted->value,
            'submission_stage' => 'under_association_review',
            'affiliation_status' => 'pending',
            'affiliation_review_note' => null,
            'affiliation_reviewed_at' => null,
            'affiliation_reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'submitted_at' => now(),
        ]);

        $fresh = $memberApplication->fresh(['user', 'association', 'documents']);

        $this->logAuditAction->execute(
            $actor,
            'member_application_submitted',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        SendMemberApplicationSubmittedAssociationNotificationsJob::dispatch((int) $fresh->id)->afterCommit();

        return $fresh;
    }
}
