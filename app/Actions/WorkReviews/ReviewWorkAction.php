<?php

namespace App\Actions\WorkReviews;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Jobs\SendWorkReviewDecisionMemberNotificationsJob;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkReview;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewWorkAction
{
    public function __construct(protected LogAuditAction $logAuditAction) {}

    public function execute(Work $work, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): Work
    {
        $decision = (string) ($data['decision'] ?? '');
        $allowed = ['verified', 'approved', 'rejected', 'disputed', 'changes_requested', 'restricted'];

        if (! in_array($decision, $allowed, true)) {
            throw ValidationException::withMessages(['decision' => ['Invalid review decision provided.']]);
        }

        if (! in_array($work->work_status, [
            WorkStatus::Submitted,
            WorkStatus::UnderReview,
            WorkStatus::Verified,
            WorkStatus::Approved,
            WorkStatus::Disputed,
            WorkStatus::Restricted,
        ], true)) {
            throw ValidationException::withMessages(['work_status' => ['Only submitted or reviewable works can be reviewed.']]);
        }

        if ($decision === 'approved' && $work->verification_status !== WorkVerificationStatus::Verified) {
            throw ValidationException::withMessages(['decision' => ['A work must be verified before it can be approved.']]);
        }

        $fresh = DB::transaction(function () use ($work, $data, $actor, $decision, $ipAddress, $userAgent): Work {
            $before = $work->toArray();

            $statusMap = [
                'verified' => ['work_status' => WorkStatus::Verified->value, 'verification_status' => WorkVerificationStatus::Verified->value],
                'approved' => ['work_status' => WorkStatus::Approved->value, 'verification_status' => WorkVerificationStatus::Verified->value],
                'rejected' => ['work_status' => WorkStatus::Submitted->value, 'verification_status' => WorkVerificationStatus::Rejected->value],
                'disputed' => ['work_status' => WorkStatus::Disputed->value, 'verification_status' => WorkVerificationStatus::Disputed->value, 'is_disputed' => true],
                'changes_requested' => ['work_status' => WorkStatus::ChangesRequested->value, 'verification_status' => WorkVerificationStatus::Pending->value, 'verified_at' => null, 'verified_by_user_id' => null],
                'restricted' => ['work_status' => WorkStatus::Restricted->value, 'verification_status' => $work->verification_status === WorkVerificationStatus::Verified ? WorkVerificationStatus::Verified->value : WorkVerificationStatus::Pending->value, 'is_restricted' => true],
            ];

            $payload = array_merge($statusMap[$decision], [
                'last_reviewed_by_user_id' => $actor->id,
                'last_reviewed_at' => now(),
                'review_reason' => $data['review_note'] ?? null,
                'governance_reason_code' => $data['reason_code'] ?? $work->governance_reason_code,
                'governance_reason' => $decision === 'restricted' ? ($data['review_note'] ?? null) : $work->governance_reason,
            ]);

            if (in_array($decision, ['verified', 'approved'], true)) {
                $payload['verified_at'] = now();
                $payload['verified_by_user_id'] = $actor->id;
                $payload['is_disputed'] = false;
                $payload['is_restricted'] = false;
            }

            if ($decision === 'rejected') {
                $payload['verified_at'] = null;
                $payload['verified_by_user_id'] = null;
            }

            $work->forceFill($payload)->save();

            WorkReview::create([
                'work_id' => $work->id,
                'reviewer_user_id' => $actor->id,
                'decision' => $decision,
                'reason_code' => $data['reason_code'] ?? null,
                'review_note' => $data['review_note'] ?? null,
                'evidence_requested' => (bool) ($data['evidence_requested'] ?? false),
                'reviewed_at' => now(),
                'metadata_json' => Arr::only($data, ['duplicate_candidates', 'claim_flags']),
            ]);

            $fresh = $work->fresh(['member.user', 'contributors.disputedBy', 'reviews.reviewer', 'verifier', 'lastReviewer']);

            $this->logAuditAction->execute(
                $actor,
                'work_reviewed',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });

        if (in_array($decision, ['verified', 'approved', 'rejected', 'changes_requested'], true)) {
            SendWorkReviewDecisionMemberNotificationsJob::dispatch((int) $fresh->id, $decision)->afterCommit();
        }

        return $fresh;
    }
}
