<?php

namespace App\Actions\Dashboard;

use App\Http\Resources\Api\V1\AssociationResource;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\MemberApplication;
use App\Models\User;
use App\Support\DashboardPayload;

class BuildAssociationDashboardAction
{
    public function execute(User $user): array
    {
        $association = $user->associations()->first();

        abort_if(! $association, 403, 'Association officer is not linked to any association.');

        $applicationsQuery = MemberApplication::query()
            ->where('association_id', $association->id);

        $recentApplications = $applicationsQuery
            ->with(['user.roles', 'association', 'documents'])
            ->latest()
            ->limit(10)
            ->get();

        $applicationIds = MemberApplication::query()
            ->where('association_id', $association->id)
            ->pluck('id');

        $recentActivity = AuditLog::query()
            ->with('actor')
            ->where(function ($query) use ($association, $applicationIds) {
                $query->where(function ($nested) use ($association) {
                    $nested->where('subject_type', Association::class)
                        ->where('subject_id', $association->id);
                });

                if ($applicationIds->isNotEmpty()) {
                    $query->orWhere(function ($nested) use ($applicationIds) {
                        $nested->where('subject_type', MemberApplication::class)
                            ->whereIn('subject_id', $applicationIds->all());
                    });
                }
            })
            ->latest('created_at')
            ->limit(DashboardPayload::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(fn (AuditLog $log) => DashboardPayload::serializeAuditLog($log))
            ->values()
            ->all();

        return [
            'meta' => DashboardPayload::meta(),
            'association' => new AssociationResource($association),
            'stats' => [
                'total_applications' => (clone $applicationsQuery)->count(),
                'submitted_applications' => (clone $applicationsQuery)->where('application_status', 'submitted')->count(),
                'approved_applications' => (clone $applicationsQuery)->where('application_status', 'approved')->count(),
                'rejected_applications' => (clone $applicationsQuery)->where('application_status', 'rejected')->count(),
                'changes_requested_applications' => (clone $applicationsQuery)->where('application_status', 'changes_requested')->count(),
            ],
            'recent_applications' => MemberApplicationResource::collection($recentApplications),
            'recent_activity' => $recentActivity,
        ];
    }
}
