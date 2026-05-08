<?php

use App\Models\InstitutionAnnualDeclaration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores declaration supporting documents on configured filesystem disk', function () {
    ensureRole('institution_user');

    config()->set('filesystems.default', 's3');
    Storage::fake('s3');

    [$user, $institution] = actingAsInstitutionUserWithInstitution();
    $institution->update([
        'institution_type' => 'corporate_organization',
    ]);

    $year = now()->year + 1;

    $this->postJson('/api/v1/institution/declarations', [
        'licensing_year' => $year,
        'declared_members_count' => 120,
        'declared_branches_count' => 3,
        'supporting_document' => UploadedFile::fake()->create('declaration.pdf', 150, 'application/pdf'),
    ])->assertCreated();

    $declaration = InstitutionAnnualDeclaration::query()
        ->where('institution_id', $institution->id)
        ->where('licensing_year', $year)
        ->firstOrFail();

    expect($declaration->supporting_document_disk)->toBe('s3')
        ->and($declaration->supporting_document_path)->not()->toBeNull();

    Storage::disk('s3')->assertExists($declaration->supporting_document_path);
});

