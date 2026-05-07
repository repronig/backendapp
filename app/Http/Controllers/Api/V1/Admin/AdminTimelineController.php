<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\TimelineEventResource;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Licence;
use App\Models\Member;
use App\Models\LicencePayment;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminTimelineController extends BaseApiController
{
    protected function subjectClass(string $entity): string
    {
        return match ($entity) {
            'member' => Member::class,
            'association' => Association::class,
            'institution' => Institution::class,
            'declaration' => InstitutionAnnualDeclaration::class,
            'licence' => Licence::class,
            'payment' => LicencePayment::class,
            'invoice' => Invoice::class,
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
