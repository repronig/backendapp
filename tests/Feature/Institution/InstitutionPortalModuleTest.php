<?php

use App\Models\Licence;

beforeEach(function () {
    ensureRole('institution_user');
});

it('denies institution routes to guests', function () {
    $this->getJson('/api/v1/institution/profile')->assertUnauthorized();
    $this->getJson('/api/v1/institution/dashboard')->assertUnauthorized();
});

it('returns institution profile for a linked institution user', function () {
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/institution/profile')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('returns institution dashboard for a linked institution user', function () {
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/institution/dashboard')
        ->assertOk()
        ->assertJsonPath('message', 'Institution dashboard retrieved successfully.')
        ->assertJsonStructure([
            'data' => [
                'meta',
                'institution',
                'stats',
                'onboarding_status',
                'recent_licences',
                'recent_usage_declarations',
                'recent_annual_declarations',
                'recent_activity',
            ],
        ]);
});

it('lists institution licences', function () {
    [, $institution] = actingAsInstitutionUserWithInstitution();

    Licence::factory()->create([
        'institution_id' => $institution->id,
        'licence_status' => 'draft',
    ]);

    $this->getJson('/api/v1/institution/licences')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists institution declarations index', function () {
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/institution/declarations')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists institution invoices', function () {
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/institution/invoices')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists usage declarations', function () {
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/institution/usage-declarations')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});
