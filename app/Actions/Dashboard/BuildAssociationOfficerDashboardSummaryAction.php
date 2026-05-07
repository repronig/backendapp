<?php

namespace App\Actions\Dashboard;

use App\Http\Resources\Api\V1\AssociationResource;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\MemberApplication;
use App\Models\User;

class BuildAssociationOfficerDashboardSummaryAction
{
    public function execute(User $user): ?array
    {
        if (! $user->hasRole('association_officer')) {
            return null;
        }

        $association = $user->associations()->first();

        if (! $association) {
            return null;
        }

        $applicationsQuery = MemberApplication::query()
            ->where('association_id', $association->id);

        return [
            'association' => new AssociationResource($association),
            'stats' => [
                'total_applications' => (clone $applicationsQuery)->count(),
                'submitted_applications' => (clone $applicationsQuery)->where('application_status', 'submitted')->count(),
                'approved_applications' => (clone $applicationsQuery)->where('application_status', 'approved')->count(),
                'rejected_applications' => (clone $applicationsQuery)->where('application_status', 'rejected')->count(),
                'changes_requested_applications' => (clone $applicationsQuery)->where('application_status', 'changes_requested')->count(),
            ],
            'recent_applications' => MemberApplicationResource::collection(
                $applicationsQuery->with(['user.roles', 'association', 'documents'])->latest()->limit(5)->get()
            ),
        ];
    }
}