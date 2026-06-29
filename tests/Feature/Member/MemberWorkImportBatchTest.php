<?php

use App\Jobs\MemberWorkImports\ProcessMemberWorkImportFilesJob;
use App\Jobs\MemberWorkImports\ProcessMemberWorkImportPreviewJob;
use App\Jobs\MemberWorkImports\ProcessMemberWorkImportSubmitReadyJob;
use App\Jobs\SendMemberWorkImportBatchCompletedNotificationJob;
use App\Jobs\SendWorkSubmittedAdminNotificationsJob;
use App\Models\ImportBatch;
use App\Models\Member;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkContributor;
use App\Enums\WorkStatus;
use App\Support\MemberWorkImports\MemberWorkImportCsv;
use Database\Seeders\LanguageSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    ensureRole('member');
    $this->seed(LanguageSeeder::class);
    Storage::fake(config('filesystems.default', 'local'));
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
});

function uniqueImportIsbn(): string
{
    return '9783161'.str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function memberWorkImportCsvContents(array $overrides = []): string
{
    $row = array_merge([
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'title' => 'Bulk Imported Work',
        'primary_language' => 'English',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484101',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'synopsis' => 'A useful educational text for rights management.',
        'subtitle' => '',
        'publication_year' => '2026',
        'doi' => '',
        'publisher_name' => 'REPRONIG Test Press',
        'notes' => '',
        'other_work_type' => '',
        'target_market_other' => '',
    ], $overrides);

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, MemberWorkImportCsv::allColumns());
    fputcsv($handle, array_map(
        fn (string $column) => $row[$column] ?? '',
        MemberWorkImportCsv::allColumns()
    ));
    rewind($handle);
    $contents = stream_get_contents($handle) ?: '';
    fclose($handle);

    return $contents;
}

function uploadMemberWorkImportCsv(array $overrides = []): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'works-import.csv',
        memberWorkImportCsvContents($overrides)
    );
}

it('downloads the member work import template', function () {
    [$user] = actingAsApprovedMember();

    $response = $this->get('/api/v1/work-import-batches/template');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('member-works-import-template.csv');
});

it('downloads the bulk works column reference pdf', function () {
    actingAsApprovedMember();

    $response = $this->get('/api/v1/work-import-batches/column-reference', [
        'Accept' => 'application/pdf',
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    expect($response->headers->get('content-disposition'))->toContain('repronig-bulk-works-column-reference.pdf');
});

it('allows an approved member to preview a valid csv batch', function () {
    [$user, $member] = actingAsApprovedMember();

    $response = $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => uniqueImportIsbn()]),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.import_type', 'member_works');

    $batch = ImportBatch::query()->first();
    expect($batch)->not->toBeNull();
    expect($batch->member_id)->toBe($member->id);

    $path = $batch->summary_json['source_path'];
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $path);

    $batch->refresh();
    expect($batch->status)->toBe('validated')
        ->and($batch->valid_rows)->toBe(1)
        ->and($batch->invalid_rows)->toBe(0);
});

it('blocks unapproved members from creating import batches', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    Member::factory()->create([
        'user_id' => $user->id,
        'approval_status' => 'pending',
        'account_status' => 'active',
    ]);

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['member_application']);

    $this->get('/api/v1/work-import-batches/template')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['member_application']);
});

it('records row failures for invalid enum values', function () {
    actingAsApprovedMember();

    $response = $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['type_of_work' => 'not_a_real_type']),
    ]);

    $response->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $batch->refresh();
    expect($batch->valid_rows)->toBe(0)
        ->and($batch->invalid_rows)->toBe(1)
        ->and($batch->failures)->toHaveCount(1);
});

it('records row failures for duplicate identifiers', function () {
    actingAsApprovedMember();

    $duplicateIsbn = uniqueImportIsbn();

    Work::factory()->create([
        'identifier_type' => 'isbn',
        'identifier_value' => $duplicateIsbn,
    ]);

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => $duplicateIsbn]),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $batch->refresh();
    expect($batch->valid_rows)->toBe(0)
        ->and($batch->invalid_rows)->toBe(1);
});

it('creates draft works with a default contributor after process confirmation', function () {
    [$user, $member] = actingAsApprovedMember();
    $importIsbn = uniqueImportIsbn();

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => $importIsbn]),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $this->postJson("/api/v1/work-import-batches/{$batch->id}/process", [
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ])->assertOk();

    $batch->refresh();
    expect($batch->status)->toBe('processed')
        ->and($batch->processed_rows)->toBe(1);

    $work = Work::query()->where('member_id', $member->id)->where('title', 'Bulk Imported Work')->first();
    expect($work)->not->toBeNull()
        ->and($work->work_status)->toBe(WorkStatus::Draft)
        ->and($work->agreement_accepted)->toBeTrue();

    $contributor = WorkContributor::query()->where('work_id', $work->id)->first();
    expect($contributor)->not->toBeNull()
        ->and((float) $contributor->ownership_percentage)->toBe(100.0)
        ->and($contributor->contributor_role)->toBe('Author');
});

it('attaches cover files from zip and marks works ready', function () {
    [$user, $member] = actingAsApprovedMember();
    $importIsbn = uniqueImportIsbn();

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => $importIsbn]),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $this->postJson("/api/v1/work-import-batches/{$batch->id}/process", [
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ])->assertOk();

    $batch->refresh();

    $zipPath = 'imports/member-works/test.zip';
    $zip = new ZipArchive;
    $absoluteZip = Storage::path($zipPath);
    @mkdir(dirname($absoluteZip), 0755, true);
    $zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $coverContents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $zip->addFromString($importIsbn.'_cover.png', $coverContents ?: '');
    $zip->close();

    ProcessMemberWorkImportFilesJob::dispatchSync($batch->id, $zipPath);

    $batch->refresh();
    expect($batch->ready_rows)->toBe(1);

    $item = $batch->memberWorkImportItems()->first();
    expect($item->status)->toBe('ready')
        ->and($item->file_results_json['attached'] ?? [])->not->toBeEmpty();
});

it('records zip mime rejection failures on the import item row', function () {
    actingAsApprovedMember();
    $importIsbn = uniqueImportIsbn();

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => $importIsbn]),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $this->postJson("/api/v1/work-import-batches/{$batch->id}/process", [
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ])->assertOk();

    $zipPath = 'imports/member-works/invalid-mime.zip';
    $zip = new ZipArchive;
    $absoluteZip = Storage::path($zipPath);
    @mkdir(dirname($absoluteZip), 0755, true);
    $zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString($importIsbn.'_cover.png', 'not an image');
    $zip->close();

    ProcessMemberWorkImportFilesJob::dispatchSync($batch->id, $zipPath);

    $item = $batch->memberWorkImportItems()->first();
    expect($item->file_results_json['failed'] ?? [])->not->toBeEmpty();
    expect($item->status)->toBe('draft');
});

it('lists import items for a batch', function () {
    [$user, $member] = actingAsApprovedMember();
    $importIsbn = uniqueImportIsbn();

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => $importIsbn]),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $this->postJson("/api/v1/work-import-batches/{$batch->id}/process", [
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ])->assertOk();

    $this->getJson("/api/v1/work-import-batches/{$batch->id}/items")
        ->assertOk()
        ->assertJsonPath('data.0.status', 'draft')
        ->assertJsonPath('data.0.row_payload.identifier_value', $importIsbn);
});

it('prevents a member from reading another members import batch', function () {
    actingAsApprovedMember();

    $otherUser = User::factory()->create(['account_type' => 'member', 'email_verified_at' => now()]);
    $otherUser->assignRole('member');
    $otherMember = Member::factory()->create([
        'user_id' => $otherUser->id,
        'approval_status' => 'approved',
        'account_status' => 'active',
    ]);

    $batch = ImportBatch::factory()->create([
        'import_type' => 'member_works',
        'member_id' => $otherMember->id,
        'created_by_user_id' => $otherUser->id,
        'status' => 'validated',
    ]);

    $this->getJson("/api/v1/work-import-batches/{$batch->id}")
        ->assertForbidden();

    $this->getJson("/api/v1/work-import-batches/{$batch->id}/items")
        ->assertForbidden();
});

it('allows two rows with the same identifier value but different identifier types', function () {
    actingAsApprovedMember();

    $sharedValue = 'shared-id-'.random_int(1000, 9999);
    $rowOne = [
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'title' => 'ISBN Work',
        'primary_language' => 'English',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => $sharedValue,
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'synopsis' => 'First row synopsis.',
        'subtitle' => '',
        'publication_year' => '2026',
        'doi' => '',
        'publisher_name' => '',
        'notes' => '',
        'other_work_type' => '',
        'target_market_other' => '',
    ];
    $rowTwo = array_merge($rowOne, [
        'type_of_work' => 'fiction_text',
        'title' => 'ISSN Work',
        'identifier_type' => 'issn',
        'target_market' => 'general_public',
        'synopsis' => 'Second row synopsis.',
    ]);

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, MemberWorkImportCsv::allColumns());
    fputcsv($handle, array_map(fn (string $column) => $rowOne[$column] ?? '', MemberWorkImportCsv::allColumns()));
    fputcsv($handle, array_map(fn (string $column) => $rowTwo[$column] ?? '', MemberWorkImportCsv::allColumns()));
    rewind($handle);
    $contents = stream_get_contents($handle) ?: '';
    fclose($handle);

    $this->postJson('/api/v1/work-import-batches', [
        'file' => UploadedFile::fake()->createWithContent('works-import.csv', $contents),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $batch->refresh();
    expect($batch->valid_rows)->toBe(2)
        ->and($batch->invalid_rows)->toBe(0);
});

it('returns not found when member work bulk import is disabled by env', function () {
    config(['member_work_imports.enabled' => false]);

    actingAsApprovedMember();

    $this->get('/api/v1/work-import-batches/template')->assertNotFound();
    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => uniqueImportIsbn()]),
    ])->assertNotFound();
});

it('returns not found when member work bulk import is disabled in platform settings', function () {
    config(['member_work_imports.enabled' => true]);

    actingAsApiUser('admin', ['account_type' => 'admin']);
    $this->putJson('/api/v1/admin/works/bulk-import-settings', [
        'member_work_bulk_import_enabled' => false,
    ])->assertOk();

    actingAsApprovedMember();

    $this->get('/api/v1/work-import-batches/template')->assertNotFound();
});

it('submits ready works through the batch submit job', function () {
    Queue::fake([
        SendWorkSubmittedAdminNotificationsJob::class,
        SendMemberWorkImportBatchCompletedNotificationJob::class,
    ]);

    [$user, $member] = actingAsApprovedMember();
    $importIsbn = uniqueImportIsbn();

    $this->postJson('/api/v1/work-import-batches', [
        'file' => uploadMemberWorkImportCsv(['identifier_value' => $importIsbn]),
    ])->assertCreated();

    $batch = ImportBatch::query()->firstOrFail();
    ProcessMemberWorkImportPreviewJob::dispatchSync($batch->id, $batch->summary_json['source_path']);

    $this->postJson("/api/v1/work-import-batches/{$batch->id}/process", [
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ])->assertOk();

    $zipPath = 'imports/member-works/submit.zip';
    $zip = new ZipArchive;
    $absoluteZip = Storage::path($zipPath);
    @mkdir(dirname($absoluteZip), 0755, true);
    $zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $coverContents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $zip->addFromString($importIsbn.'_cover.png', $coverContents ?: '');
    $zip->close();

    ProcessMemberWorkImportFilesJob::dispatchSync($batch->id, $zipPath);

    ProcessMemberWorkImportSubmitReadyJob::dispatchSync($batch->id);

    $batch->refresh();
    expect($batch->submitted_rows)->toBe(1);

    $work = Work::query()->where('identifier_value', $importIsbn)->first();
    expect($work->work_status)->toBe(WorkStatus::Submitted);

    Queue::assertPushed(SendMemberWorkImportBatchCompletedNotificationJob::class);
});

it('exposes member work bulk import feature flag in public platform settings', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);
    $this->putJson('/api/v1/admin/works/bulk-import-settings', [
        'member_work_bulk_import_enabled' => false,
    ])->assertOk();

    $this->getJson('/api/v1/platform-settings')
        ->assertOk()
        ->assertJsonPath('data.features.member_work_bulk_import_enabled', false);
});
