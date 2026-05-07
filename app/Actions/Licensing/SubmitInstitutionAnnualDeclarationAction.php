<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DeclarationStatus;
use App\Jobs\SendInstitutionDeclarationSubmittedAdminNotificationsJob;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitInstitutionAnnualDeclarationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
    ) {}

    public function execute(
        InstitutionAnnualDeclaration $declaration,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): InstitutionAnnualDeclaration {
        if ($declaration->declaration_status !== DeclarationStatus::Draft->value) {
            throw ValidationException::withMessages([
                'declaration_status' => ['Only draft declarations can be submitted.'],
            ]);
        }

        if (! $declaration->supporting_document_path) {
            throw ValidationException::withMessages([
                'supporting_document' => ['Upload a supporting document before submitting this declaration.'],
            ]);
        }

        $before = $declaration->toArray();

        $declaration->update([
            'declaration_status' => DeclarationStatus::Submitted->value,
            'submitted_at' => now(),
        ]);

        $fresh = $declaration->fresh(['faculties', 'licence', 'institution']);

        $this->logAuditAction->execute(
            $actor,
            'institution_annual_declaration_submitted',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        SendInstitutionDeclarationSubmittedAdminNotificationsJob::dispatch((int) $fresh->getKey())->afterCommit();

        return $fresh;
    }
}
