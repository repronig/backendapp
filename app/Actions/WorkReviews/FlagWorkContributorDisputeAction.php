<?php

namespace App\Actions\WorkReviews;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Jobs\SendWorkReviewDecisionMemberNotificationsJob;
use App\Models\User;
use App\Models\WorkContributor;
use App\Models\WorkReview;
use Illuminate\Support\Facades\DB;

class FlagWorkContributorDisputeAction
{
    public function __construct(protected LogAuditAction $logAuditAction) {}

    public function execute(WorkContributor $contributor, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): WorkContributor
    {
        $fresh = DB::transaction(function () use ($contributor, $data, $actor, $ipAddress, $userAgent): WorkContributor {
            $before = $contributor->toArray();

            $contributor->forceFill([
                'is_disputed' => true,
                'dispute_reason_code' => $data['reason_code'] ?? null,
                'dispute_reason' => $data['reason'] ?? null,
                'disputed_by_user_id' => $actor->id,
                'disputed_at' => now(),
            ])->save();

            $work = $contributor->work;
            if ($work) {
                $work->forceFill([
                    'work_status' => WorkStatus::ChangesRequested->value,
                    'verification_status' => WorkVerificationStatus::Pending->value,
                    'review_reason' => $data['reason'] ?? $work->review_reason,
                    'is_disputed' => true,
                    'last_reviewed_by_user_id' => $actor->id,
                    'last_reviewed_at' => now(),
                    'verified_at' => null,
                    'verified_by_user_id' => null,
                ])->save();

                WorkReview::create([
                    'work_id' => $work->id,
                    'reviewer_user_id' => $actor->id,
                    'decision' => 'changes_requested',
                    'reason_code' => $data['reason_code'] ?? null,
                    'review_note' => $data['reason'] ?? null,
                    'evidence_requested' => false,
                    'reviewed_at' => now(),
                    'metadata_json' => [
                        'source' => 'contributor_dispute',
                        'disputed_contributor_id' => $contributor->id,
                    ],
                ]);
            }

            $fresh = $contributor->fresh(['work', 'member', 'disputedBy']);

            $this->logAuditAction->execute(
                $actor,
                'work_contributor_disputed',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent,
            );

            return $fresh;
        });

        SendWorkReviewDecisionMemberNotificationsJob::dispatch((int) $fresh->work_id, 'changes_requested')->afterCommit();

        return $fresh;
    }
}
