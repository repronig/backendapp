<?php

use App\Models\Setting;

beforeEach(function () {
    ensureRole('institution_user');
});

function seedInstitutionLicensingTermsForAcceptance(string $version = 'pass-d-1'): void
{
    Setting::query()->updateOrCreate(
        ['group' => 'general', 'key' => 'licensing'],
        ['value' => [
            'paystack_enabled' => true,
            'flutterwave_enabled' => true,
            'default_online_gateway' => 'paystack',
            'offline_payment_enabled' => true,
            'repronig_bank' => [],
            'institution_licensing_terms' => [
                'version' => $version,
                'title' => 'Institution licensing obligations',
                'body' => 'By using REPRONIG you agree to comply with collective management rules for your sector.',
            ],
        ]]
    );
}

it('flags licensing terms acceptance in me context when terms are published and not yet accepted', function () {
    seedInstitutionLicensingTermsForAcceptance();
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.onboarding_status.institution_licensing_terms_acceptance_required', true);
});

it('does not require acceptance when the published terms body is empty', function () {
    Setting::query()->updateOrCreate(
        ['group' => 'general', 'key' => 'licensing'],
        ['value' => [
            'paystack_enabled' => true,
            'flutterwave_enabled' => true,
            'institution_licensing_terms' => [
                'version' => '1',
                'title' => 'T',
                'body' => '   ',
            ],
        ]]
    );
    actingAsInstitutionUserWithInstitution();

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.onboarding_status.institution_licensing_terms_acceptance_required', false);
});

it('accepts institution licensing terms and clears the requirement on subsequent me calls', function () {
    seedInstitutionLicensingTermsForAcceptance();
    [$user, $institution] = actingAsInstitutionUserWithInstitution();

    $this->postJson('/api/v1/institution/licensing-terms/acceptance', [
        'terms_version' => 'pass-d-1',
        'acknowledged_on' => now()->toDateString(),
        'confirm_accepted' => true,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Institution licensing terms accepted successfully.')
        ->assertJsonPath('data.licensing_terms_version_accepted', 'pass-d-1');

    $this->assertDatabaseHas('institutions', [
        'id' => $institution->id,
        'licensing_terms_version_accepted' => 'pass-d-1',
    ]);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.onboarding_status.institution_licensing_terms_acceptance_required', false);

    expect($institution->fresh()->licensing_terms_accepted_at)->not->toBeNull();
});

it('rejects acceptance when the submitted version does not match the published version', function () {
    seedInstitutionLicensingTermsForAcceptance('v-current');
    actingAsInstitutionUserWithInstitution();

    $this->postJson('/api/v1/institution/licensing-terms/acceptance', [
        'terms_version' => 'wrong',
        'acknowledged_on' => now()->toDateString(),
        'confirm_accepted' => true,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['terms_version']);
});
