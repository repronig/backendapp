<?php

namespace App\Actions\AssociationReview;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Jobs\SendMemberApprovedAdminNotificationJob;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
    ) {}

    public function execute(
        MemberApplication $memberApplication,
        User $reviewer,
        ?string $comment = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplication {
        if ($memberApplication->application_status !== MemberApplicationStatus::Submitted->value) {
            throw ValidationException::withMessages([
                'application_status' => ['Only submitted applications can be approved.'],
            ]);
        }

        if (
            ! $memberApplication->association
            || ! $memberApplication->association->is_enabled
            || $memberApplication->association->status !== 'active'
        ) {
            throw ValidationException::withMessages([
                'association' => ['This association is not allowed to review applications.'],
            ]);
        }

        return DB::transaction(function () use ($memberApplication, $reviewer, $comment, $ipAddress, $userAgent) {
            $beforeApplication = $memberApplication->toArray();

            $memberApplication->update([
                'application_status' => MemberApplicationStatus::Submitted->value,
                'affiliation_status' => 'validated',
                'submission_stage' => 'under_admin_review',
                'affiliation_reviewed_at' => now(),
                'affiliation_reviewed_by_user_id' => $reviewer->id,
                'affiliation_review_note' => $comment,
            ]);

            $freshApplication = $memberApplication->fresh(['user', 'association', 'documents']);

            $this->logAuditAction->execute(
                $reviewer,
                'member_affiliation_validated',
                $freshApplication,
                $beforeApplication,
                $freshApplication->toArray(),
                $ipAddress,
                $userAgent
            );

            SendMemberApprovedAdminNotificationJob::dispatch($freshApplication, $reviewer, 'validated')->afterCommit();

            return $freshApplication;
        });
    }
}
