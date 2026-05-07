<?php

namespace App\Actions\AssociationReview;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Events\MemberApplicationApprovedByAssociation;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\User;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected ReferenceCodeGenerator $referenceCodeGenerator,
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
                'application_status' => MemberApplicationStatus::Approved->value,
                'submission_stage' => 'completed',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'notes' => $comment ?: $memberApplication->notes,
            ]);

            $member = Member::firstOrNew(['user_id' => $memberApplication->user_id]);
            $beforeMember = $member->exists ? $member->toArray() : null;

            $member->fill([
                'association_id' => $memberApplication->association_id,
                'member_code' => $member->member_code ?: $this->referenceCodeGenerator->generateMemberCode(),
                'member_type' => $memberApplication->applicant_type,
                'member_provided_id' => $memberApplication->member_provided_id ?: $member->member_provided_id,
                'approval_status' => 'approved',
                'account_status' => 'active',
                'status_changed_by_user_id' => $reviewer->id,
                'status_changed_at' => now(),
                'joined_at' => $member->joined_at ?: now(),
                'activated_at' => now(),
            ])->save();

            $freshApplication = $memberApplication->fresh(['user', 'association', 'documents']);
            $freshMember = $member->fresh(['association', 'profile', 'user']);

            $this->logAuditAction->execute(
                $reviewer,
                'member_application_approved',
                $freshApplication,
                $beforeApplication,
                $freshApplication->toArray(),
                $ipAddress,
                $userAgent
            );

            $this->logAuditAction->execute(
                $reviewer,
                $beforeMember ? 'member_updated_from_application_approval' : 'member_created_from_application_approval',
                $freshMember,
                $beforeMember,
                $freshMember->toArray(),
                $ipAddress,
                $userAgent
            );

            event(new MemberApplicationApprovedByAssociation($freshApplication, $freshMember, $reviewer));

            return $freshApplication;
        });
    }
}
