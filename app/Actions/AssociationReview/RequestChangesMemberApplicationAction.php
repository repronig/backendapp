<?php

namespace App\Actions\AssociationReview;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\System\MemberApplicationChangesRequestedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestChangesMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {
    }

    public function execute(
        MemberApplication $memberApplication,
        User $reviewer,
        string $comment,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplication {
        if ($memberApplication->application_status !== MemberApplicationStatus::Submitted->value) {
            throw ValidationException::withMessages([
                'application_status' => ['Only submitted applications can have changes requested.'],
            ]);
        }

        return DB::transaction(function () use (
            $memberApplication,
            $reviewer,
            $comment,
            $ipAddress,
            $userAgent
        ) {
            $before = $memberApplication->toArray();

            $memberApplication->update([
                'application_status' => MemberApplicationStatus::ChangesRequested->value,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'notes' => $comment,
            ]);

            $fresh = $memberApplication->fresh([
                'user',
                'association',
                'documents',
            ]);

            $this->logAuditAction->execute(
                $reviewer,
                'member_application_changes_requested',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            $this->mailService->sendMemberApplicationChangesRequested(
                $fresh->user,
                $comment
            );

            if ($fresh->user) {
                $this->systemNotifications->send(
                    $fresh->user,
                    new MemberApplicationChangesRequestedSystemNotification($comment, $fresh->external_id),
                    'member_application_changes_requested',
                    'Member application changes requested'
                );
            }

            return $fresh;
        });
    }
}
