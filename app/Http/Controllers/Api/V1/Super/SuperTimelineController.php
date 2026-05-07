<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\TimelineEventResource;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SuperTimelineController extends BaseApiController
{
    protected function subjectClass(string $entity): string
    {
        return match ($entity) {
            'association' => Association::class,
            'user' => User::class,
            default => throw new NotFoundHttpException('Timeline not available for this entity.'),
        };
    }

    public function show(Request $request, string $entity, int $subjectId): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('actor.roles')
            ->where('subject_type', $this->subjectClass($entity))
            ->where('subject_id', $subjectId)
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->paginated('Timeline retrieved successfully.', $logs, TimelineEventResource::class);
    }
}
