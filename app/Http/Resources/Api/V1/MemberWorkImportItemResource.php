<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberWorkImportItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_number' => $this->row_number,
            'work_id' => $this->work_id,
            'status' => $this->status,
            'row_payload' => $this->row_payload_json,
            'readiness_errors' => $this->readiness_errors_json,
            'file_results' => $this->file_results_json,
            'submit_errors' => $this->submit_errors_json,
            'work' => $this->whenLoaded('work', fn () => new WorkResource($this->work)),
        ];
    }
}
