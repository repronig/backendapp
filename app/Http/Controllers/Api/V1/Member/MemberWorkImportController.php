<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Imports\CreateImportBatchAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\ProcessMemberWorkImportBatchRequest;
use App\Http\Requests\Api\V1\StoreMemberWorkImportBatchRequest;
use App\Http\Requests\Api\V1\StoreMemberWorkImportFilesRequest;
use App\Http\Resources\Api\V1\ImportRowFailureResource;
use App\Http\Resources\Api\V1\MemberWorkImportBatchResource;
use App\Http\Resources\Api\V1\MemberWorkImportItemResource;
use App\Jobs\MemberWorkImports\ProcessMemberWorkImportDraftsJob;
use App\Jobs\MemberWorkImports\ProcessMemberWorkImportFilesJob;
use App\Jobs\MemberWorkImports\ProcessMemberWorkImportPreviewJob;
use App\Jobs\MemberWorkImports\ProcessMemberWorkImportSubmitReadyJob;
use App\Models\ImportBatch;
use App\Support\MemberWorkImports\MemberWorkImportCsv;
use App\Support\Pdf\MemberWorkImportPdfPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberWorkImportController extends BaseApiController
{
    public function template(Request $request): StreamedResponse
    {
        $this->ensureApprovedMember($request);

        $filename = 'member-works-import-template.csv';

        return response()->streamDownload(function (): void {
            echo MemberWorkImportCsv::templateContents();
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function columnReference(Request $request, MemberWorkImportPdfPresenter $presenter): Response
    {
        $this->ensureApprovedMember($request);

        return Pdf::loadView('pdf.member-work-import-column-reference', $presenter->columnReferenceData())
            ->setPaper('a4', 'landscape')
            ->download('repronig-bulk-works-column-reference.pdf');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureApprovedMember($request);

        $member = $request->user()->member;
        abort_unless($member, 403);

        $batches = ImportBatch::query()
            ->where('import_type', 'member_works')
            ->where('member_id', $member->id)
            ->withCount([
                'memberWorkImportItems as draft_items_count' => fn ($query) => $query->where('status', 'draft'),
                'memberWorkImportItems as failed_items_count' => fn ($query) => $query->where('status', 'failed'),
            ])
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Work import batches retrieved successfully.',
            $batches,
            MemberWorkImportBatchResource::class
        );
    }

    public function store(
        StoreMemberWorkImportBatchRequest $request,
        CreateImportBatchAction $action,
        LogAuditAction $logAuditAction,
    ): JsonResponse {
        $this->ensureApprovedMember($request);

        $member = $request->user()->member;
        abort_unless($member, 403);

        $file = $request->file('file');
        $contents = (string) file_get_contents($file->getRealPath());
        $parsed = MemberWorkImportCsv::parse($contents);
        $path = $file->storeAs('imports/member-works', uniqid('member_work_').'.csv');

        $batch = $action->execute(
            $request->user(),
            'member_works',
            $file->getClientOriginalName(),
            count($parsed['rows']),
            $member
        );

        $batch->update([
            'summary_json' => [
                'source_path' => $path,
                'mode' => 'preview',
            ],
        ]);

        ProcessMemberWorkImportPreviewJob::dispatch($batch->id, $path);

        $logAuditAction->execute(
            $request->user(),
            'member_work_import_created',
            $batch,
            null,
            ['source_filename' => $batch->source_filename],
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Work import preview queued successfully.',
            new MemberWorkImportBatchResource($batch)
        );
    }

    public function show(Request $request, ImportBatch $workImportBatch): JsonResponse
    {
        $this->ensureApprovedMember($request);
        $this->authorize('view', $workImportBatch);

        $workImportBatch->loadCount([
            'memberWorkImportItems as draft_items_count' => fn ($query) => $query->where('status', 'draft'),
            'memberWorkImportItems as failed_items_count' => fn ($query) => $query->where('status', 'failed'),
        ]);

        return $this->success(
            'Work import batch retrieved successfully.',
            new MemberWorkImportBatchResource($workImportBatch)
        );
    }

    public function failures(Request $request, ImportBatch $workImportBatch): JsonResponse
    {
        $this->ensureApprovedMember($request);
        $this->authorize('view', $workImportBatch);

        $failures = $workImportBatch->failures()->orderBy('row_number')->paginate($this->perPage($request));

        return $this->paginated(
            'Work import failures retrieved successfully.',
            $failures,
            ImportRowFailureResource::class
        );
    }

    public function items(Request $request, ImportBatch $workImportBatch): JsonResponse
    {
        $this->ensureApprovedMember($request);
        $this->authorize('view', $workImportBatch);

        $items = $workImportBatch->memberWorkImportItems()
            ->with('work')
            ->orderBy('row_number')
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Work import items retrieved successfully.',
            $items,
            MemberWorkImportItemResource::class
        );
    }

    public function process(
        ProcessMemberWorkImportBatchRequest $request,
        ImportBatch $workImportBatch,
    ): JsonResponse {
        $this->ensureApprovedMember($request);
        $this->authorize('process', $workImportBatch);

        abort_unless(in_array($workImportBatch->status, ['validated', 'validated_with_errors'], true), 422, 'Only validated imports can be processed.');
        abort_if(($workImportBatch->valid_rows ?? 0) < 1, 422, 'This batch has no valid rows to process.');

        $sourcePath = $workImportBatch->summary_json['source_path'] ?? null;
        abort_unless($sourcePath && Storage::exists($sourcePath), 422, 'Import batch does not have a stored source file path.');

        $workImportBatch->update([
            'status' => 'processing',
            'agreement_accepted' => true,
            'date_of_consent' => $request->validated('date_of_consent'),
        ]);

        ProcessMemberWorkImportDraftsJob::dispatch($workImportBatch->id, $sourcePath);

        return $this->success(
            'Draft work creation queued successfully.',
            new MemberWorkImportBatchResource($workImportBatch->fresh())
        );
    }

    public function uploadFiles(
        StoreMemberWorkImportFilesRequest $request,
        ImportBatch $workImportBatch,
    ): JsonResponse {
        $this->ensureApprovedMember($request);
        $this->authorize('uploadFiles', $workImportBatch);

        abort_unless(in_array($workImportBatch->status, ['processed', 'processed_with_errors', 'submitted', 'submitted_with_errors'], true), 422, 'Upload files after draft works have been created.');

        $file = $request->file('file');
        $path = $file->storeAs('imports/member-works', uniqid('member_work_zip_').'.zip');

        ProcessMemberWorkImportFilesJob::dispatch($workImportBatch->id, $path);

        return $this->success(
            'ZIP file attachment queued successfully.',
            new MemberWorkImportBatchResource($workImportBatch->fresh())
        );
    }

    public function submitReady(Request $request, ImportBatch $workImportBatch): JsonResponse
    {
        $this->ensureApprovedMember($request);
        $this->authorize('submitReady', $workImportBatch);

        abort_unless(in_array($workImportBatch->status, ['processed', 'processed_with_errors', 'submitted', 'submitted_with_errors'], true), 422, 'Submit ready works after drafts have been created.');
        abort_if(($workImportBatch->ready_rows ?? 0) < 1, 422, 'This batch has no ready works to submit.');

        ProcessMemberWorkImportSubmitReadyJob::dispatch($workImportBatch->id);

        return $this->success(
            'Submit ready works queued successfully.',
            new MemberWorkImportBatchResource($workImportBatch->fresh())
        );
    }

    public function errorReport(Request $request, ImportBatch $workImportBatch): StreamedResponse|JsonResponse
    {
        $this->ensureApprovedMember($request);
        $this->authorize('view', $workImportBatch);

        $reportPath = $workImportBatch->error_report_path;
        abort_unless($reportPath && Storage::exists($reportPath), 404, 'No error report is available for this batch.');

        return response()->streamDownload(function () use ($reportPath): void {
            echo Storage::get($reportPath);
        }, 'member-work-import-'.$workImportBatch->id.'-errors.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function ensureApprovedMember(Request $request): void
    {
        $member = $request->user()?->member;

        if (! $member || $member->approval_status !== 'approved') {
            throw ValidationException::withMessages([
                'member_application' => ['Your application is still under review. Once approved, you will be able to upload your works. Thank you.'],
            ]);
        }
    }
}
