<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Models\Institution;
use App\Models\User;
use App\Notifications\System\InstitutionRejectedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectInstitutionAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {}

    public function execute(Institution $institution, User $actor, ?string $reason = null, ?string $ipAddress = null, ?string $userAgent = null): Institution
    {
        if (! in_array($institution->onboarding_status, ['submitted', 'under_review', 'draft', 'approved'], true)) {
            throw ValidationException::withMessages([
                'onboarding_status' => ['This institution cannot be rejected in its current state.'],
            ]);
        }

        $fresh = DB::transaction(function () use ($institution, $actor, $reason, $ipAddress, $userAgent) {
            $before = $institution->toArray();

            $institution->update([
                'onboarding_status' => 'rejected',
                'account_status' => 'inactive',
                'governance_status' => 'restricted',
                'governance_reason_code' => 'rejected',
                'governance_reason' => $reason,
                'governance_changed_by_user_id' => $actor->id,
                'governance_changed_at' => now(),
                'approved_by_user_id' => null,
                'approved_at' => null,
            ]);

            $after = $institution->fresh(['profile', 'state', 'city']);
            $this->logAuditAction->execute($actor, 'institution_rejected', $after, $before, $after->toArray(), $ipAddress, $userAgent);

            return $after;
        });

        $forNotify = $fresh->fresh(['institutionUsers.user']);

        $this->mailService->sendInstitutionRejected($forNotify, $reason);

        foreach ($forNotify->institutionUsers ?? [] as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $this->systemNotifications->send(
                $institutionUser->user,
                new InstitutionRejectedSystemNotification($forNotify->external_id, $reason),
                'institution_rejected',
                'Institution registration rejected'
            );
        }

        return $fresh;
    }
}
