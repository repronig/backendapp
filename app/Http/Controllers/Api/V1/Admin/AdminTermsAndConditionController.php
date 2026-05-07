<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Terms\StoreTermsAndConditionAction;
use App\Actions\Terms\UpdateTermsAndConditionAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreTermsAndConditionRequest;
use App\Http\Requests\Api\V1\UpdateTermsAndConditionRequest;
use App\Http\Resources\Api\V1\TermsAndConditionResource;
use App\Models\TermsAndCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTermsAndConditionController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $terms = TermsAndCondition::query()
            ->when($request->filled('audience'), fn ($query) => $query->where('audience', $request->string('audience')->value()))
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Terms and conditions retrieved successfully.',
            $terms,
            TermsAndConditionResource::class
        );
    }

    public function store(StoreTermsAndConditionRequest $request, StoreTermsAndConditionAction $action): JsonResponse
    {
        $terms = $action->execute($request->validated(), $request->user());

        return $this->created('Terms and conditions created successfully.', new TermsAndConditionResource($terms));
    }

    public function show(TermsAndCondition $termsAndCondition): JsonResponse
    {
        return $this->success('Terms and conditions retrieved successfully.', new TermsAndConditionResource($termsAndCondition));
    }

    public function update(UpdateTermsAndConditionRequest $request, TermsAndCondition $termsAndCondition, UpdateTermsAndConditionAction $action): JsonResponse
    {
        $terms = $action->execute($termsAndCondition, $request->validated(), $request->user());

        return $this->success('Terms and conditions updated successfully.', new TermsAndConditionResource($terms));
    }

    public function destroy(TermsAndCondition $termsAndCondition): JsonResponse
    {
        $termsAndCondition->delete();

        return $this->success('Terms and conditions deleted successfully.');
    }
}
