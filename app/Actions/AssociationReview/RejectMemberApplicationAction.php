<?php

namespace App\Actions\AssociationReview;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Jobs\SendMemberApprovedAdminNotificationJob;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
    ) {
    }

    public function execute(
        MemberApplication $memberApplication,
        User $reviewer,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        bool $requiresAffiliationDecision = false
    ): MemberApplication {
        if ($memberApplication->application_status !== MemberApplicationStatus::Submitted->value) {
            throw ValidationException::withMessages([
                'application_status' => ['Only submitted applications can be rejected.'],
            ]);
        }

        if (
            $requiresAffiliationDecision
            && ! in_array((string) $memberApplication->affiliation_status, ['validated', 'rejected'], true)
        ) {
            throw ValidationException::withMessages([
                'affiliation_status' => ['Association affiliation decision is required before final rejection.'],
            ]);
        }

        return DB::transaction(function () use (
            $memberApplication,
            $reviewer,
            $reason,
            $ipAddress,
            $userAgent
        ) {
            $before = $memberApplication->toArray();

            $memberApplication->update([
                'application_status' => MemberApplicationStatus::Submitted->value,
                'affiliation_status' => 'rejected',
                'submission_stage' => 'under_admin_review',
                'affiliation_reviewed_at' => now(),
                'affiliation_reviewed_by_user_id' => $reviewer->id,
                'affiliation_review_note' => $reason,
            ]);

            $fresh = $memberApplication->fresh([
                'user',
                'association',
                'documents',
            ]);

            $this->logAuditAction->execute(
                $reviewer,
                'member_affiliation_rejected',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            SendMemberApprovedAdminNotificationJob::dispatch($fresh, $reviewer, 'rejected')->afterCommit();

            return $fresh;
        });
    }
}
