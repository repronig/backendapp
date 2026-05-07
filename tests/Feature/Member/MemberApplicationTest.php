<?php

use App\Mail\Associations\MemberApplicationSubmittedAssociationMailable;
use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    ensureRole('member');
});

it('requires nationality when creating a member application', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $payload = validMemberApplicationPayload(['nationality' => null]);

    $response = $this->postJson('/api/v1/member-applications', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['nationality']);

    expect($user->memberApplication)->toBeNull();
});

it('creates an author member application with the current onboarding fields', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $response = $this->postJson('/api/v1/member-applications', validMemberApplicationPayload());

    $response->assertCreated()
        ->assertJsonPath('data.applicant_type', 'author')
        ->assertJsonPath('data.nationality', 'Nigerian')
        ->assertJsonPath('data.consent_accepted', true);

    $this->assertDatabaseHas('member_applications', [
        'user_id' => $user->id,
        'applicant_type' => 'author',
        'member_author_type' => 'individual',
        'member_author_category' => 'author',
        'nationality' => 'Nigerian',
        'consent_accepted' => true,
    ]);

    expect($user->fresh()->first_name)->toBe('Ada')
        ->and($user->fresh()->last_name)->toBe('Author');
});

it('persists optional member_provided_id on member application create', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'member_provided_id' => 'NIN-998877',
    ]))
        ->assertCreated()
        ->assertJsonPath('data.member_provided_id', 'NIN-998877');

    $this->assertDatabaseHas('member_applications', [
        'user_id' => $user->id,
        'member_provided_id' => 'NIN-998877',
    ]);
});

it('stores only the current application document types', function () {
    Storage::fake('public');

    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $application = MemberApplication::factory()->create([
        'user_id' => $user->id,
        'application_status' => 'draft',
    ]);

    $response = $this->postJson("/api/v1/member-applications/{$application->id}/documents", [
        'document_type' => 'proof_of_id',
        'file' => UploadedFile::fake()->image('id-card.jpg'),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.document_type', 'proof_of_id');

    $this->assertDatabaseHas('member_application_documents', [
        'member_application_id' => $application->id,
        'document_type' => 'proof_of_id',
        'uploaded_by_user_id' => $user->id,
    ]);

    $this->postJson("/api/v1/member-applications/{$application->id}/documents", [
        'document_type' => 'passport_photo',
        'file' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertUnprocessable()->assertJsonValidationErrors(['document_type']);
});

it('does not submit a member application until proof of id and proof of address exist', function () {
    Mail::fake();
    ensureRole('association_officer');
    $officer = User::factory()->create([
        'account_type' => 'association_officer',
        'email' => 'officer@example.test',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $officer->assignRole('association_officer');

    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $application = MemberApplication::factory()->create([
        'user_id' => $user->id,
        'application_status' => 'draft',
        'consent_accepted' => true,
        'consent_date' => now()->toDateString(),
    ]);
    $officer->associations()->attach($application->association_id, [
        'designation_title' => 'Reviewer',
        'is_active' => true,
    ]);

    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_id',
        'uploaded_by_user_id' => $user->id,
    ]);

    $this->postJson("/api/v1/member-applications/{$application->id}/submit")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['documents']);

    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_address',
        'uploaded_by_user_id' => $user->id,
    ]);

    $this->postJson("/api/v1/member-applications/{$application->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.application_status', 'submitted')
        ->assertJsonPath('data.submission_stage', 'under_association_review');

    Mail::assertQueued(MemberApplicationSubmittedAssociationMailable::class, 1);
    expect(NotificationLog::query()
        ->where('notification_key', 'member_application_submitted_association')
        ->where('channel', 'email')
        ->where('user_id', $officer->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'member_application_submitted_association')
            ->where('channel', 'system')
            ->where('user_id', $officer->id)
            ->exists())->toBeTrue();
});
