<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Actions\Integrations\BuildIntegrationOutboxSummaryAction;
use App\Actions\Integrations\EnqueueWipoConnectPayloadAction;
use App\Actions\Integrations\RequeueIntegrationOutboxEntryAction;
use App\Actions\Integrations\UpsertExternalIntegrationAction;
use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\EnqueueWipoConnectOutboxRequest;
use App\Http\Requests\Api\V1\ListIntegrationOutboxRequest;
use App\Http\Requests\Api\V1\UpsertExternalIntegrationRequest;
use App\Http\Resources\Api\V1\ExternalIntegrationResource;
use App\Http\Resources\Api\V1\IntegrationOutboxEntryResource;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SuperIntegrationController extends BaseApiController
{
    protected function integrationListPerPage(Request $request): int
    {
        return $this->perPage($request, 25, 100);
    }

    protected function queryIntegrations()
    {
        return ExternalIntegration::query()
            ->orderBy('provider')
            ->orderBy('environment');
    }

    protected function queryOutboxEntries(array $validated)
    {
        $query = IntegrationOutboxEntry::query()->latest('id');

        if (isset($validated['status'])) {
            $query->where('status', IntegrationSyncStatus::from($validated['status']));
        }

        if (isset($validated['provider'])) {
            $query->where('provider', IntegrationProvider::from($validated['provider']));
        }

        return $query;
    }

    public function integrationsIndex(Request $request): JsonResponse
    {
        $paginator = $this->queryIntegrations()
            ->paginate($this->integrationListPerPage($request));

        return $this->paginated(
            'External integrations retrieved successfully.',
            $paginator,
            ExternalIntegrationResource::class
        );
    }

    public function integrationsUpsert(
        UpsertExternalIntegrationRequest $request,
        UpsertExternalIntegrationAction $action
    ): JsonResponse {
        $data = $request->validated();

        $integration = $action->execute(
            IntegrationProvider::from($data['provider']),
            IntegrationEnvironment::from($data['environment']),
            $data,
        );

        return $this->success(
            'External integration saved successfully.',
            (new ExternalIntegrationResource($integration))->resolve()
        );
    }

    public function outboxSummary(BuildIntegrationOutboxSummaryAction $action): JsonResponse
    {
        return $this->success(
            'Integration outbox summary retrieved successfully.',
            $action->execute()
        );
    }

    public function outboxIndex(ListIntegrationOutboxRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $paginator = $this->queryOutboxEntries($validated)
            ->paginate($this->integrationListPerPage($request));

        return $this->paginated(
            'Integration outbox retrieved successfully.',
            $paginator,
            IntegrationOutboxEntryResource::class
        );
    }

    public function enqueueWipoConnectOutbox(
        EnqueueWipoConnectOutboxRequest $request,
        Work $work,
        EnqueueWipoConnectPayloadAction $action
    ): JsonResponse {
        $this->authorize('view', $work);

        $validated = $request->validated();

        $operation = $validated['operation'] ?? 'sync_work';
        $environment = isset($validated['environment'])
            ? IntegrationEnvironment::from($validated['environment'])
            : null;

        $result = $action->execute(
            $work,
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
            : 'A pending outbox entry already exists for this work and operation.';

        return $this->success(
            $message,
            (new IntegrationOutboxEntryResource($result['entry']))->resolve(),
            $result['created'] ? 201 : 200,
        );
    }

    public function requeueOutboxEntry(
        IntegrationOutboxEntry $integrationOutboxEntry,
        RequeueIntegrationOutboxEntryAction $action
    ): JsonResponse {
        try {
            $entry = $action->execute($integrationOutboxEntry);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            'Outbox entry requeued for delivery.',
            (new IntegrationOutboxEntryResource($entry))->resolve()
        );
    }
}
