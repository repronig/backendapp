<?php

namespace App\Actions\Dashboard;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\DashboardPayload;

class BuildMeDashboardSummaryAction
{
    public function __construct(
        protected BuildMemberDashboardSummaryAction $buildMemberDashboardSummaryAction,
        protected BuildAssociationOfficerDashboardSummaryAction $buildAssociationOfficerDashboardSummaryAction,
        protected BuildInstitutionDashboardSummaryAction $buildInstitutionDashboardSummaryAction,
        protected BuildAdminAccessSummaryAction $buildAdminAccessSummaryAction
    ) {}

    public function execute(User $user): array
    {
        $user->load([
            'roles',
            'associations',
            'member.association',
            'member.profile',
            'institutionUsers.institution',
        ]);

        return [
            'meta' => DashboardPayload::meta(),
            'user' => new UserResource($user),
            'roles' => $user->roles->pluck('name')->values()->all(),
            'member' => $this->buildMemberDashboardSummaryAction->execute($user),
            'association_officer' => $this->buildAssociationOfficerDashboardSummaryAction->execute($user),
            'institution' => $this->buildInstitutionDashboardSummaryAction->execute($user),
            'admin' => $this->buildAdminAccessSummaryAction->execute($user),
        ];
    }
}
