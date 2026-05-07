<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WorkCollection extends ResourceCollection
{
    public $collects = WorkResource::class;

    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
        ];
    }
}