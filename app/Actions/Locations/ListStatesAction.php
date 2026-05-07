<?php

namespace App\Actions\Locations;

use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListStatesAction
{
    public function execute(int $perPage = 100): LengthAwarePaginator
    {
        return State::query()->orderBy('name')->paginate($perPage);
    }
}
