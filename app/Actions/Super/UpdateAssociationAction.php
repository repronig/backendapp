<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\Association;
use App\Models\User;

class UpdateAssociationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Association $association,
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Association {
        $before = $association->toArray();

        $association->update($data);

        $fresh = $association->fresh();

        $this->logAuditAction->execute(
            $actor,
            'association_updated',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}