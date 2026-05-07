<?php

namespace App\Actions\Dashboard;

use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Http\Resources\Api\V1\MemberProfileResource;
use App\Http\Resources\Api\V1\WorkResource;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\User;
use App\Models\Work;
use App\Support\DashboardPayload;
use App\Support\ProfileCompleteness;

class BuildMemberDashboardSummaryAction
{
    public function execute(User $user): ?array
    {
        if (! $user->hasRole('member')) {
            return null;
        }

        $member = $user->member?->load([
            'user.roles',
            'association',
            'profile',
        ]);

        $memberApplication = MemberApplication::query()
            ->with(['user.roles', 'association', 'documents'])
            ->where('user_id', $user->id)
            ->first();

        $worksQuery = Work::query()
            ->when($member, fn ($q) => $q->where('member_id', $member->id));

        $recentWorks = $member
            ? $worksQuery->with(['contributors.disputedBy', 'files', 'verifier', 'lastReviewer'])->latest()->limit(5)->get()
            : collect();

        $profileCompleteness = $member ? ProfileCompleteness::make([
            'full_name' => $member->user?->name,
            'email' => $member->user?->email,
            'phone' => $member->user?->phone,
            'date_of_birth' => $member->profile?->date_of_birth,
            'occupation' => $member->profile?->occupation,
            'address_line_1' => $member->profile?->residential_address_line_1,
            'city' => $member->profile?->city,
            'state' => $member->profile?->state,
            'country' => $member->profile?->country,
            'association' => $member->association?->name,
        ]) : null;
        $documentsCount = $memberApplication?->documents?->count() ?? 0;

        $pendingActions = array_values(array_filter([
            ! $memberApplication ? [
                'key' => 'start_application',
                'label' => 'Complete membership application',
                'description' => 'Submit your membership application so it can be reviewed.',
                'priority' => 'high',
            ] : null,
            $memberApplication && ! $member ? [
                'key' => 'await_approval',
                'label' => 'Track application review',
                'description' => 'Your application is in review. Monitor updates and requested changes.',
                'priority' => 'medium',
            ] : null,
            $memberApplication && $documentsCount === 0 ? [
                'key' => 'upload_supporting_documents',
                'label' => 'Upload supporting documents',
                'description' => 'Add your verification documents to strengthen your application.',
                'priority' => 'medium',
            ] : null,
            $profileCompleteness && ! ($profileCompleteness['is_complete'] ?? false) ? [
                'key' => 'complete_profile',
                'label' => 'Complete your member profile',
                'description' => 'Fill the missing profile fields to improve account readiness.',
                'priority' => 'medium',
                'missing_fields' => $profileCompleteness['missing_fields'] ?? [],
            ] : null,
            $member && (clone $worksQuery)->count() === 0 ? [
                'key' => 'register_first_work',
                'label' => 'Register your first work',
                'description' => 'Create your first repertoire record to begin submissions.',
                'priority' => 'low',
            ] : null,
        ]));

        $recentActivity = AuditLog::query()
            ->with('actor')
            ->where(function ($query) use ($user, $member, $memberApplication, $recentWorks) {
                $query->where('actor_user_id', $user->id);

                if ($member) {
                    $query->orWhere(function ($nested) use ($member) {
                        $nested->where('subject_type', Member::class)
                            ->where('subject_id', $member->id);
                    });
                }

                if ($memberApplication) {
                    $query->orWhere(function ($nested) use ($memberApplication) {
                        $nested->where('subject_type', MemberApplication::class)
                            ->where('subject_id', $memberApplication->id);
                    });
                }

                if ($recentWorks->isNotEmpty()) {
                    $query->orWhere(function ($nested) use ($recentWorks) {
                        $nested->where('subject_type', Work::class)
                            ->whereIn('subject_id', $recentWorks->pluck('id')->all());
                    });
                }
            })
            ->latest('created_at')
            ->limit(DashboardPayload::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(fn (AuditLog $log) => DashboardPayload::serializeAuditLog($log))
            ->values()
            ->all();

        $recentSubmissions = $recentWorks
            ->filter(fn ($work) => ! is_null($work->submitted_at))
            ->take(3)
            ->values();

        return [
            'member_profile' => $member ? new MemberProfileResource($member) : null,
            'member_application' => $memberApplication ? new MemberApplicationResource($memberApplication) : null,
            'profile_completeness' => $profileCompleteness,
            'stats' => [
                'total_works' => $member ? (clone $worksQuery)->count() : 0,
                'draft_works' => $member ? (clone $worksQuery)->where('work_status', 'draft')->count() : 0,
                'submitted_works' => $member ? (clone $worksQuery)->where('work_status', 'submitted')->count() : 0,
                'verified_works' => $member ? (clone $worksQuery)->where('verification_status', 'verified')->count() : 0,
                'approved_works' => $member ? (clone $worksQuery)->where('work_status', 'approved')->count() : 0,
            ],
            'recent_works' => WorkResource::collection($recentWorks),
            'recent_submissions' => WorkResource::collection($recentSubmissions),
            'recent_activity' => $recentActivity,
            'pending_actions' => $pendingActions,
            'onboarding_status' => [
                'application_status' => $memberApplication?->application_status,
                'submission_stage' => $memberApplication?->submission_stage,
                'approved_member' => $member !== null,
            ],
        ];
    }
}
