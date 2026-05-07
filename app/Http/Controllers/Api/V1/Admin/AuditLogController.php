<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with(['actor.roles'])
            ->when(
                $request->filled('action'),
                fn ($q) => $q->where('action', $request->string('action')->value())
            )
            ->when(
                $request->filled('subject_type'),
                fn ($q) => $q->where('subject_type', $request->string('subject_type')->value())
            )
            ->when(
                $request->filled('subject_id'),
                fn ($q) => $q->where('subject_id', (int) $request->integer('subject_id'))
            )
            ->latest('created_at')
            ->paginate($this->perPage($request, 20));

        return $this->paginated(
            'Audit logs retrieved successfully.',
            $logs,
            AuditLogResource::class
        );
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return $this->success(
            'Audit log retrieved successfully.',
            new AuditLogResource($auditLog->load(['actor.roles']))
        );
    }
}