<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DeclarationStatus;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use App\Notifications\System\DeclarationRejectedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectInstitutionAnnualDeclarationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {}

    public function execute(InstitutionAnnualDeclaration $declaration, User $actor, ?string $reason = null, ?string $ipAddress = null, ?string $userAgent = null): InstitutionAnnualDeclaration
    {
        if (! in_array($declaration->declaration_status, [DeclarationStatus::Submitted->value, DeclarationStatus::UnderReview->value], true)) {
            throw ValidationException::withMessages([
                'declaration_status' => ['Only submitted or under-review declarations can be rejected.'],
            ]);
        }

        return DB::transaction(function () use ($declaration, $actor, $reason, $ipAddress, $userAgent) {
            $before = $declaration->toArray();

            $declaration->update([
                'declaration_status' => DeclarationStatus::Rejected->value,
                'approved_at' => null,
                'approved_by_user_id' => null,
                'invoice_due_date' => null,
                'metadata_json' => array_merge($declaration->metadata_json ?? [], ['rejection_reason' => $reason]),
            ]);

            $fresh = $declaration->fresh(['institution.institutionUsers.user', 'faculties', 'licence']);

            $this->logAuditAction->execute(
                $actor,
                'institution_annual_declaration_rejected',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            foreach ($fresh->institution?->institutionUsers ?? [] as $institutionUser) {
                if (! $institutionUser->is_active || ! $institutionUser->user) {
                    continue;
                }

                $this->systemNotifications->send(
                    $institutionUser->user,
                    new DeclarationRejectedSystemNotification($fresh->id, $reason, (string) $fresh->licensing_year),
                    'declaration_rejected',
                    'Declaration rejected'
                );
            }

            $this->mailService->sendDeclarationRejected($fresh, $reason);

            return $fresh;
        });
    }
}
