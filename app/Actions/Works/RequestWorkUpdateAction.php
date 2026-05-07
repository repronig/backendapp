<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Jobs\SendWorkUpdateRequestedNotificationJob;
use App\Models\Member;
use App\Models\Work;
use Illuminate\Validation\ValidationException;

class RequestWorkUpdateAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        Work $work,
        Member $member,
        ?string $note = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Work {
        if ((int) $work->member_id !== (int) $member->id) {
            throw ValidationException::withMessages([
                'work' => ['You can only request updates for your own work.'],
            ]);
        }

        if (($work->update_request_status ?? null) === 'pending') {
            throw ValidationException::withMessages([
                'update_request_status' => ['An update request is already pending for this work.'],
            ]);
        }

        $before = $work->toArray();

        $work->forceFill([
            'update_request_status' => 'pending',
            'update_requested_at' => now(),
            'update_requested_by_member_id' => $member->id,
            'update_request_note' => $note,
            'update_request_reviewed_at' => null,
            'update_request_reviewed_by_user_id' => null,
            'update_request_review_note' => null,
        ])->save();

        $fresh = $work->fresh(['member.user', 'contributors.disputedBy', 'files', 'verifier', 'lastReviewer']);

        $this->logAuditAction->execute(
            $member->user,
            'work_update_requested',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        SendWorkUpdateRequestedNotificationJob::dispatch($fresh);

        return $fresh;
    }
}
