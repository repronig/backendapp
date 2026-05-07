<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Imports\CreateImportBatchAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreImportBatchRequest;
use App\Http\Resources\Api\V1\ImportBatchResource;
use App\Jobs\Imports\ProcessCsvImportJob;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminImportController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $imports = ImportBatch::query()->latest()->paginate($this->perPage($request));

        return $this->paginated('Import batches retrieved successfully.', $imports, ImportBatchResource::class);
    }

    public function store(StoreImportBatchRequest $request, CreateImportBatchAction $action): JsonResponse
    {
        $file = $request->file('file');
        $rows = array_filter(array_map('str_getcsv', file($file->getRealPath())));
        $path = $file->storeAs('imports', uniqid('import_') . '.csv');
        $batch = $action->execute($request->user(), $request->validated('import_type'), $file->getClientOriginalName(), max(count($rows) - 1, 0));
        $batch->update(['summary_json' => ['source_path' => $path, 'mode' => 'preview']]);

        ProcessCsvImportJob::dispatch($batch->id, $request->validated('import_type'), $path, 'preview');

        return $this->created('Import batch validation queued successfully.', new ImportBatchResource($batch));
    }

    public function process(ImportBatch $importBatch): JsonResponse
    {
        $sourcePath = $importBatch->summary_json['source_path'] ?? null;

        abort_unless($sourcePath, 422, 'Import batch does not have a stored source file path.');
        abort_if(! in_array($importBatch->status, ['validated', 'processed_with_errors'], true), 422, 'Only validated imports can be processed.');

        $importBatch->update(['status' => 'processing']);
        ProcessCsvImportJob::dispatch($importBatch->id, $importBatch->import_type, $sourcePath, 'process');

        return $this->success('Import batch processing queued successfully.', new ImportBatchResource($importBatch->fresh()));
    }
}
