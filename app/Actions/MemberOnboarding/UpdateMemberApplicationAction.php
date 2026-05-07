<?php

namespace App\Actions\MemberOnboarding;

use App\Actions\Audit\LogAuditAction;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        MemberApplication $memberApplication,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplication {
        if (! $memberApplication->isEditableByApplicant()) {
            throw ValidationException::withMessages([
                'member_application' => ['Only draft or changes-requested applications can be updated.'],
            ]);
        }

        $before = $memberApplication->toArray();

        $applicant = $memberApplication->user;
        if ($applicant) {
            SyncMemberApplicantLegalNames::fromApplicationPayload($applicant, $data);
        }

        $memberApplication->update($data);

        $fresh = $memberApplication->fresh(['association', 'documents', 'user']);

        $this->logAuditAction->execute(
            $actor,
            'member_application_updated',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
