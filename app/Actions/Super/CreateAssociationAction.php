<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\Association;
use App\Models\User;

class CreateAssociationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Association {
        $association = Association::query()->create($data);

        $fresh = $association->fresh();

        $this->logAuditAction->execute(
            $actor,
            'association_created',
            $fresh,
            null,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}