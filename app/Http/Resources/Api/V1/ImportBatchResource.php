<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'import_type' => $this->import_type,
            'status' => $this->status,
            'source_filename' => $this->source_filename,
            'total_rows' => $this->total_rows,
            'valid_rows' => $this->valid_rows,
            'invalid_rows' => $this->invalid_rows,
            'processed_rows' => $this->processed_rows,
            'error_report_path' => $this->error_report_path,
            'summary' => $this->summary_json,
            'validated_at' => $this->validated_at,
            'processed_at' => $this->processed_at,
            'failures' => $this->whenLoaded('failures'),
        ];
    }
}
