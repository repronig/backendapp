<?php

namespace App\Actions\WorkReviews;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Jobs\SendWorkUpdateRequestReviewedNotificationJob;
use App\Models\User;
use App\Models\Work;
use Illuminate\Validation\ValidationException;

class ReviewWorkUpdateRequestAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        Work $work,
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Work {
        if (($work->update_request_status ?? null) !== 'pending') {
            throw ValidationException::withMessages([
                'update_request_status' => ['There is no pending update request for this work.'],
            ]);
        }

        $decision = (string) ($data['decision'] ?? '');
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'decision' => ['Invalid update request decision.'],
            ]);
        }

        $before = $work->toArray();

        $payload = [
            'update_request_status' => $decision,
            'update_request_reviewed_at' => now(),
            'update_request_reviewed_by_user_id' => $actor->id,
            'update_request_review_note' => $data['review_note'] ?? null,
        ];

        if ($decision === 'approved') {
            $payload = array_merge($payload, [
                'work_status' => WorkStatus::Draft->value,
                'verification_status' => WorkVerificationStatus::Pending->value,
                'submitted_at' => null,
                'verified_at' => null,
                'verified_by_user_id' => null,
                'review_reason' => null,
                'last_reviewed_by_user_id' => null,
                'last_reviewed_at' => null,
                'is_disputed' => false,
                'is_restricted' => false,
            ]);
        }

        $work->forceFill($payload)->save();

        $fresh = $work->fresh(['member.user', 'contributors.disputedBy', 'files', 'verifier', 'lastReviewer']);

        $this->logAuditAction->execute(
            $actor,
            'work_update_request_reviewed',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        SendWorkUpdateRequestReviewedNotificationJob::dispatch($fresh, $decision);

        return $fresh;
    }
}
