<?php

namespace App\Actions\AssociationReview;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\System\MemberApplicationRejectedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectMemberApplicationAction
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
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplication {
        if ($memberApplication->application_status !== MemberApplicationStatus::Submitted->value) {
            throw ValidationException::withMessages([
                'application_status' => ['Only submitted applications can be rejected.'],
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
                'application_status' => MemberApplicationStatus::Rejected->value,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'notes' => $reason,
            ]);

            $fresh = $memberApplication->fresh([
                'user',
                'association',
                'documents',
            ]);

            $this->logAuditAction->execute(
                $reviewer,
                'member_application_rejected',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            $this->mailService->sendMemberApplicationRejected(
                $fresh->user,
                $reason
            );

            if ($fresh->user) {
                $this->systemNotifications->send(
                    $fresh->user,
                    new MemberApplicationRejectedSystemNotification($reason, $fresh->external_id),
                    'member_application_rejected',
                    'Member application rejected'
                );
            }

            return $fresh;
        });
    }
}
