<?php

beforeEach(function () {
    ensureRole('member');
});

function validOrgMemberApplicationFields(array $overrides = []): array
{
    return array_merge([
        'publisher_organisation_name' => 'Acme Publishing Ltd',
        'publisher_location_address' => '12 Marina Road, Lagos',
        'publisher_postal_address' => 'PO Box 100, Lagos',
        'publisher_email' => 'contact@acme.test',
        'publisher_phone' => '+2348010000001',
        'country_of_residence' => 'Nigeria',
        'nationality' => null,
        'next_of_kin_name' => null,
        'next_of_kin_phone' => null,
    ], $overrides);
}

it('creates a publisher individual application with next of kin fields', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $response = $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'publisher',
        'association_id' => associationForApplicantType('publisher')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'book_publisher',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.applicant_type', 'publisher')
        ->assertJsonPath('data.member_author_type', 'individual')
        ->assertJsonPath('data.member_author_category', 'book_publisher');

    $this->assertDatabaseHas('member_applications', [
        'user_id' => $user->id,
        'applicant_type' => 'publisher',
        'member_author_type' => 'individual',
        'member_author_category' => 'book_publisher',
    ]);
});

it('creates a publisher corporate application without tax identification number', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'publisher',
        'association_id' => associationForApplicantType('publisher')->id,
        'member_author_type' => 'corporate',
        'member_author_category' => 'magazine_publisher',
        ...validOrgMemberApplicationFields(),
    ]))
        ->assertCreated()
        ->assertJsonPath('data.member_author_type', 'corporate')
        ->assertJsonPath('data.publisher_tin', null);
});

it('requires publisher type and category for publisher applications', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'publisher',
        'association_id' => associationForApplicantType('publisher')->id,
        'member_author_type' => null,
        'member_author_category' => null,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['member_author_type', 'member_author_category']);
});

it('requires organisation fields for publisher corporate applications', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'publisher',
        'association_id' => associationForApplicantType('publisher')->id,
        'member_author_type' => 'corporate',
        'member_author_category' => 'newspaper_publisher',
        ...validOrgMemberApplicationFields([
            'publisher_organisation_name' => null,
        ]),
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['publisher_organisation_name']);
});

it('rejects publisher applications with author-only categories', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'publisher',
        'association_id' => associationForApplicantType('publisher')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'journalist',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['member_author_category']);
});

it('creates an author corporate application with organisation fields', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'member_author_type' => 'agent',
        'member_author_category' => 'journalist',
        ...validOrgMemberApplicationFields(),
    ]))
        ->assertCreated()
        ->assertJsonPath('data.member_author_type', 'agent')
        ->assertJsonPath('data.member_author_category', 'journalist');
});

it('does not require next of kin for author corporate applications', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'member_author_type' => 'corporate',
        'member_author_category' => 'photographer',
        ...validOrgMemberApplicationFields(),
    ]))
        ->assertCreated();
});

it('requires next of kin for author individual applications', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'author',
        'next_of_kin_name' => null,
        'next_of_kin_phone' => null,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['next_of_kin_name', 'next_of_kin_phone']);
});

it('rejects author applications with trimmed invalid author categories', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'illustrator',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['member_author_category']);
});

it('does not require nationality for corporate member applications', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'member_author_type' => 'corporate',
        'member_author_category' => 'other',
        ...validOrgMemberApplicationFields(),
    ]))
        ->assertCreated();
});

it('requires nationality for individual member applications', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/member-applications', validMemberApplicationPayload([
        'applicant_type' => 'author',
        'association_id' => associationForApplicantType('author')->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'author',
        'nationality' => null,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nationality']);
});
