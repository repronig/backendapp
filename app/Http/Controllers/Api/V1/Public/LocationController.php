<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Actions\Locations\ListCitiesByStateAction;
use App\Actions\Locations\ListStatesAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\CityResource;
use App\Http\Resources\Api\V1\StateResource;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends BaseApiController
{
    public function states(Request $request, ListStatesAction $action): JsonResponse
    {
        $states = $action->execute($this->perPage($request, 100));

        return $this->paginated('States retrieved successfully.', $states, StateResource::class);
    }

    public function cities(State $state, Request $request, ListCitiesByStateAction $action): JsonResponse
    {
        $cities = $action->execute($state, $this->perPage($request, 100));

        return $this->paginated('Cities retrieved successfully.', $cities, CityResource::class);
    }
}
