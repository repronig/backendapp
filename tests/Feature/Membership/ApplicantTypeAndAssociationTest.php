<?php

use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\User;

beforeEach(function () {
    ensureRole('member');
    ensureRole('association_officer');
});

it('registers an artist member with the society of nigerian artists', function () {
    $association = associationForApplicantType('artist');

    $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Zainab',
        'last_name' => 'Artist',
        'email' => 'zainab.artist@example.com',
        'phone' => '+2348099990001',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'artist',
        'association_id' => $association->id,
        'accepted_terms' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.member_application.applicant_type', 'artist');

    $this->assertDatabaseHas('member_applications', [
        'association_id' => $association->id,
        'applicant_type' => 'artist',
    ]);
});

it('rejects member registration when association does not match applicant type', function () {
    $publisherAssociation = associationForApplicantType('publisher');

    $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Wrong',
        'last_name' => 'Match',
        'email' => 'wrong.match@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $publisherAssociation->id,
        'accepted_terms' => true,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['association_id']);
});

it('filters public associations by applicant type', function () {
    associationForApplicantType('author');
    $publisher = associationForApplicantType('publisher');
    associationForApplicantType('artist');

    $this->getJson('/api/v1/associations?applicant_type=publisher')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $publisher->id)
        ->assertJsonPath('data.0.code', 'NPA');
});

it('creates an artist member application with artist category rules', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $response = $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'artist',
        'association_id' => associationForApplicantType('artist')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'painter',
        'next_of_kin_name' => 'Kin Artist',
        'next_of_kin_phone' => '+2348011112222',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.applicant_type', 'artist')
        ->assertJsonPath('data.member_author_category', 'painter');

    $this->assertDatabaseHas('member_applications', [
        'user_id' => $user->id,
        'applicant_type' => 'artist',
        'member_author_category' => 'painter',
    ]);
});

it('rejects artist applications with author-only categories', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'artist',
        'association_id' => associationForApplicantType('artist')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'journalist',
        'next_of_kin_name' => 'Kin Artist',
        'next_of_kin_phone' => '+2348011112222',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['member_author_category']);
});

it('rejects changing applicant type on member application update', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $application = MemberApplication::factory()->create([
        'user_id' => $user->id,
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'application_status' => 'draft',
    ]);

    $this->patchJson("/api/v1/member-applications/{$application->id}", [
        'applicant_type' => 'publisher',
        'bank_name' => 'GTBank',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['applicant_type']);
});

it('rejects changing association on member application update', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $application = MemberApplication::factory()->create([
        'user_id' => $user->id,
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'application_status' => 'draft',
    ]);

    $this->patchJson("/api/v1/member-applications/{$application->id}", [
        'association_id' => associationForApplicantType('publisher')->id,
        'bank_name' => 'GTBank',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['association_id']);
});

it('omits download_url for association officers viewing application documents', function () {
    [, $association] = actingAsAssociationOfficer();

    $applicant = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $application = MemberApplication::factory()->create([
        'user_id' => $applicant->id,
        'association_id' => $association->id,
        'application_status' => 'submitted',
    ]);

    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_id',
        'uploaded_by_user_id' => $applicant->id,
        'file_path' => 'member-application-documents/test-id.pdf',
        'file_name' => 'test-id.pdf',
    ]);

    $response = $this->getJson("/api/v1/association/applications/{$application->id}")
        ->assertOk();

    $documents = $response->json('data.documents');
    expect($documents)->toBeArray()->not->toBeEmpty();
    expect($documents[0]['file_url'])->not->toBeNull();
    expect($documents[0]['download_url'])->toBeNull();
});

it('includes download_url for members viewing their own application documents', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $application = MemberApplication::factory()->create([
        'user_id' => $user->id,
        'application_status' => 'draft',
    ]);

    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_id',
        'uploaded_by_user_id' => $user->id,
        'file_path' => 'member-application-documents/member-id.pdf',
        'file_name' => 'member-id.pdf',
    ]);

    $response = $this->getJson('/api/v1/member-applications/me')->assertOk();

    $documents = $response->json('data.documents');
    expect($documents[0]['file_url'])->not->toBeNull();
    expect($documents[0]['download_url'])->not->toBeNull();
});
