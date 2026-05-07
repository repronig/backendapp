<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Models\WorkContributor;

class DeleteWorkContributorAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        WorkContributor $contributor,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $before = $contributor->toArray();

        $contributor->delete();

        $this->logAuditAction->execute(
            $actor,
            'work_contributor_deleted',
            $contributor,
            $before,
            null,
            $ipAddress,
            $userAgent
        );
    }
}