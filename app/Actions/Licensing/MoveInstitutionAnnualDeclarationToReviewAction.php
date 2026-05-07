<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DeclarationStatus;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class MoveInstitutionAnnualDeclarationToReviewAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(InstitutionAnnualDeclaration $declaration, User $actor, ?string $note = null, ?string $ipAddress = null, ?string $userAgent = null): InstitutionAnnualDeclaration
    {
        if ($declaration->declaration_status !== DeclarationStatus::Submitted->value) {
            throw ValidationException::withMessages([
                'declaration_status' => ['Only submitted declarations can be moved to review.'],
            ]);
        }

        $before = $declaration->toArray();

        $declaration->update([
            'declaration_status' => DeclarationStatus::UnderReview->value,
            'metadata_json' => array_merge($declaration->metadata_json ?? [], ['review_note' => $note]),
        ]);

        $fresh = $declaration->fresh(['institution', 'faculties', 'licence']);

        $this->logAuditAction->execute(
            $actor,
            'institution_annual_declaration_moved_to_review',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
