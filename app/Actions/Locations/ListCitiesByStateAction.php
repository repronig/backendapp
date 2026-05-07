<?php

namespace App\Actions\Locations;

use App\Models\City;
use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCitiesByStateAction
{
    public function execute(State $state, int $perPage = 200): LengthAwarePaginator
    {
        return City::query()->where('state_id', $state->id)->orderBy('name')->paginate($perPage);
    }
}
