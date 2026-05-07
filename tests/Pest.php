<?php

use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

function ensureRole(string $role): Role
{
    return Role::findOrCreate($role, 'web');
}

function actingAsApiUser(string $role, array $attributes = []): User
{
    ensureRole($role);

    $user = User::factory()->create(array_merge([
        'account_type' => $role,
        'email_verified_at' => now(),
    ], $attributes));

    $user->assignRole($role);
    Sanctum::actingAs($user);

    return $user;
}

function validMemberApplicationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ada',
        'last_name' => 'Author',
        'applicant_type' => 'author',
        'association_id' => Association::factory()->create()->id,
        'member_author_type' => 'individual',
        'member_author_category' => 'author',
        'nationality' => 'Nigerian',
        'country_of_residence' => 'Nigeria',
        'is_diaspora' => false,
        'bank_name' => 'Access Bank',
        'bank_account_number' => '0123456789',
        'bank_account_owner_name' => 'Ada Author',
        'next_of_kin_name' => 'Tunde Author',
        'next_of_kin_phone' => '+2348012345678',
        'consent_accepted' => true,
        'consent_date' => now()->toDateString(),
        'notes' => 'Test application',
    ], $overrides);
}

function validWorkPayload(array $overrides = []): array
{
    return array_merge([
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'title' => 'Introduction to Copyright Practice',
        'subtitle' => 'A practical guide',
        'publication_year' => 2026,
        'synopsis' => 'A useful educational text for rights management.',
        'primary_language' => 'English',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484100',
        'publisher_name' => 'REPRONIG Test Press',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
        'notes' => 'Test work note',
    ], $overrides);
}

/**
 * @return array{0: User, 1: Member}
 */
function actingAsApprovedMember(): array
{
    ensureRole('member');

    $user = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('member');

    $member = Member::factory()->create([
        'user_id' => $user->id,
        'member_type' => 'author',
        'approval_status' => 'approved',
        'account_status' => 'active',
    ]);

    Sanctum::actingAs($user);

    return [$user, $member];
}

/**
 * @return array{0: User, 1: Institution}
 */
function actingAsInstitutionUserWithInstitution(): array
{
    ensureRole('institution_user');

    $institution = Institution::factory()->create([
        'account_status' => 'active',
        'onboarding_status' => 'approved',
        'governance_status' => 'normal',
    ]);

    $user = User::factory()->create([
        'account_type' => 'institution_user',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('institution_user');

    InstitutionUser::factory()->create([
        'institution_id' => $institution->id,
        'user_id' => $user->id,
        'is_primary' => true,
        'is_active' => true,
        'role_label' => 'primary_contact',
    ]);

    Sanctum::actingAs($user);

    return [$user, $institution];
}

/**
 * @return array{0: User, 1: Association}
 */
function actingAsAssociationOfficer(): array
{
    ensureRole('association_officer');

    $association = Association::factory()->create();

    $user = User::factory()->create([
        'account_type' => 'association_officer',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('association_officer');
    $user->associations()->attach($association->id, [
        'designation_title' => 'Test Officer',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    return [$user, $association];
}
