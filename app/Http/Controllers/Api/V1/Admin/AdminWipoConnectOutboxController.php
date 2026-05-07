<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Integrations\EnqueueWipoConnectPayloadAction;
use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\EnqueueWipoConnectOutboxRequest;
use App\Http\Resources\Api\V1\IntegrationOutboxEntryResource;
use App\Models\Institution;
use App\Models\IntegrationOutboxEntry;
use App\Models\Licence;
use App\Models\Member;
use App\Models\Work;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWipoConnectOutboxController extends BaseApiController
{
    public function indexForWork(Request $request, Work $work): JsonResponse
    {
        $this->authorize('review', $work);

        return $this->paginatedOutboxForSubject(
            $request,
            $work,
            'WIPO Connect outbox entries for this work retrieved successfully.'
        );
    }

    public function indexForInstitution(Request $request, Institution $institution): JsonResponse
    {
        $this->authorize('view', $institution);

        return $this->paginatedOutboxForSubject(
            $request,
            $institution,
            'WIPO Connect outbox entries for this institution retrieved successfully.'
        );
    }

    public function indexForMember(Request $request, Member $member): JsonResponse
    {
        $this->authorize('view', $member);

        return $this->paginatedOutboxForSubject(
            $request,
            $member,
            'WIPO Connect outbox entries for this member retrieved successfully.'
        );
    }

    public function indexForLicence(Request $request, Licence $licence): JsonResponse
    {
        $this->authorize('view', $licence);

        return $this->paginatedOutboxForSubject(
            $request,
            $licence,
            'WIPO Connect outbox entries for this licence retrieved successfully.'
        );
    }

    private function paginatedOutboxForSubject(Request $request, Model $subject, string $message): JsonResponse
    {
        $perPage = $this->perPage($request, 25, 100);

        $paginator = IntegrationOutboxEntry::query()
            ->where('provider', IntegrationProvider::WipoConnect)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->latest('id')
            ->paginate($perPage);

        return $this->paginated(
            $message,
            $paginator,
            IntegrationOutboxEntryResource::class
        );
    }

    public function enqueueWork(
        EnqueueWipoConnectOutboxRequest $request,
        Work $work,
        EnqueueWipoConnectPayloadAction $action
    ): JsonResponse {
        $this->authorize('review', $work);

        return $this->enqueueResponse(
            $action,
            $work,
            $request->validated(),
            $request->validated()['operation'] ?? 'sync_work'
        );
    }

    public function enqueueInstitution(
        EnqueueWipoConnectOutboxRequest $request,
        Institution $institution,
        EnqueueWipoConnectPayloadAction $action
    ): JsonResponse {
        $this->authorize('view', $institution);

        return $this->enqueueResponse(
            $action,
            $institution,
            $request->validated(),
            $request->validated()['operation'] ?? 'sync_institution'
        );
    }

    public function enqueueMember(
        EnqueueWipoConnectOutboxRequest $request,
        Member $member,
        EnqueueWipoConnectPayloadAction $action
    ): JsonResponse {
        $this->authorize('view', $member);

        return $this->enqueueResponse(
            $action,
            $member,
            $request->validated(),
            $request->validated()['operation'] ?? 'sync_member'
        );
    }

    public function enqueueLicence(
        EnqueueWipoConnectOutboxRequest $request,
        Licence $licence,
        EnqueueWipoConnectPayloadAction $action
    ): JsonResponse {
        $this->authorize('view', $licence);

        return $this->enqueueResponse(
            $action,
            $licence,
            $request->validated(),
            $request->validated()['operation'] ?? 'sync_licence'
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function enqueueResponse(
        EnqueueWipoConnectPayloadAction $action,
        Work|Institution|Member|Licence $subject,
        array $validated,
        string $operation
    ): JsonResponse {
        $environment = isset($validated['environment'])
            ? IntegrationEnvironment::from($validated['environment'])
            : null;

        $result = $action->execute(
            $subject,
            $validated['payload'] ?? [],
            $operation,
            $environment,
        );

        if ($result === null) {
            return $this->error(
                'WIPO Connect is not enabled for the requested environment.',
                422
            );
        }

        $message = $result['created']
            ? 'WIPO Connect outbox entry queued successfully.'
            : 'A pending outbox entry already exists for this subject and operation.';

        return $this->success(
            $message,
            (new IntegrationOutboxEntryResource($result['entry']))->resolve(),
            $result['created'] ? 201 : 200,
        );
    }
}
