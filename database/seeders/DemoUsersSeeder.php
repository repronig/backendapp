<?php

namespace Database\Seeders;

use App\Actions\AssociationReview\ApproveMemberApplicationAction;
use App\Actions\MemberOnboarding\CreateMemberApplicationAction;
use App\Actions\MemberOnboarding\SubmitMemberApplicationAction;
use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Member;
use App\Models\MemberApplicationDocument;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {

            //NPA
            $association_npa = Association::query()->where('code', 'NPA')->firstOrFail();

            $officer_npa = User::query()->updateOrCreate(
                ['email' => 'info@npa.com'],
                [
                    'first_name' => 'Ngozi',
                    'last_name' => 'Adeyemi',
                    'phone' => '+2348000000011',
                    'password' => Hash::make('NPA@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_npa->syncRoles(['association_officer']);

            $association_npa->users()->syncWithoutDetaching([
                $officer_npa->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);


            //ANA
            $association_ana = Association::query()->where('code', 'ana')->firstOrFail();

            $officer_ana = User::query()->updateOrCreate(
                ['email' => 'info@ana.com'],
                [
                    'first_name' => 'Ngozi',
                    'last_name' => 'Adeyemi',
                    'phone' => '+2348000000011',
                    'password' => Hash::make('ANA@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_ana->syncRoles(['association_officer']);

            $association_ana->users()->syncWithoutDetaching([
                $officer_ana->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);

            //ANFAAN
            $association_anfaan = Association::query()->where('code', 'anfaan')->firstOrFail();

            $officer_anfaan = User::query()->updateOrCreate(
                ['email' => 'info@anfaan.com'],
                [
                    'first_name' => 'Ngozi',
                    'last_name' => 'Adeyemi',
                    'phone' => '+2348000000011',
                    'password' => Hash::make('ANFAAN@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_anfaan->syncRoles(['association_officer']);

            $association_anfaan->users()->syncWithoutDetaching([
                $officer_anfaan->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);


            //SNA
            $association_sna = Association::query()->where('code', 'sna')->firstOrFail();

            $officer_sna = User::query()->updateOrCreate(
                ['email' => 'info@sna.com'],
                [
                    'first_name' => 'Ngozi',
                    'last_name' => 'Adeyemi',
                    'phone' => '+2348000000011',
                    'password' => Hash::make('SNA@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_sna->syncRoles(['association_officer']);

            $association_sna->users()->syncWithoutDetaching([
                $officer_sna->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);

            /*

            $memberUser = User::query()->updateOrCreate(
                ['email' => 'member.demo@repronig.com'],
                [
                    'first_name' => 'Amina',
                    'last_name' => 'Okoro',
                    'phone' => '+2348000000010',
                    'password' => Hash::make('Password123!'),
                    'account_type' => 'member',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $memberUser->syncRoles(['member']);

            if (! $memberUser->member) {
                $application = app(CreateMemberApplicationAction::class)->execute(
                    $memberUser,
                    [
                        'association_id' => $association->id,
                        'applicant_type' => 'author',
                        'nationality' => 'Nigerian',
                        'country_of_residence' => 'Nigeria',
                        'is_diaspora' => false,
                        'member_author_type' => 'individual',
                        'member_author_category' => 'author',
                        'next_of_kin_name' => 'Chinedu Okoro',
                        'next_of_kin_phone' => '+2348000000013',
                        'bank_name' => 'Access Bank',
                        'bank_account_number' => '0123456789',
                        'bank_account_owner_name' => 'Amina Okoro',
                        'consent_accepted' => true,
                        'consent_date' => now()->toDateString(),
                        'notes' => 'Demo seeded member application.',
                        'member_provided_id' => 'NPA-DEMO-EXT-001',
                    ],
                    '127.0.0.1',
                    'DemoUsersSeeder'
                );

                MemberApplicationDocument::query()->firstOrCreate([
                    'member_application_id' => $application->id,
                    'document_type' => 'proof_of_id',
                ], [
                    'uploaded_by_user_id' => $memberUser->id,
                    'file_path' => 'documents/demo-nin.pdf',
                    'file_name' => 'nin.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 120000,
                    'verification_status' => 'pending',
                ]);

                MemberApplicationDocument::query()->firstOrCreate([
                    'member_application_id' => $application->id,
                    'document_type' => 'proof_of_address',
                ], [
                    'uploaded_by_user_id' => $memberUser->id,
                    'file_path' => 'documents/demo-proof-of-address.pdf',
                    'file_name' => 'proof-of-address.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 125000,
                    'verification_status' => 'pending',
                ]);

                $submitted = app(SubmitMemberApplicationAction::class)->execute($application->fresh('documents'), $memberUser, '127.0.0.1', 'DemoUsersSeeder');

                app(ApproveMemberApplicationAction::class)->execute(
                    $submitted,
                    $officer,
                    'Seed demo member approved.',
                    '127.0.0.1',
                    'DemoUsersSeeder'
                );
            }

            $member = Member::query()->where('user_id', $memberUser->id)->firstOrFail();
            $member->update([
                'member_type' => 'author',
                'approval_status' => 'approved',
                'account_status' => 'active',
                'joined_at' => $member->joined_at ?: now()->subDays(14),
                'activated_at' => $member->activated_at ?: now()->subDays(14),
            ]);

            MemberProfile::query()->updateOrCreate(
                ['member_id' => $member->id],
                [
                    'date_of_birth' => '1990-08-12',
                    'occupation' => 'Writer',
                    'residential_address_line_1' => '12 Bodija Road',
                    'residential_address_line_2' => 'Suite 4',
                    'city' => 'Ibadan',
                    'state' => 'Oyo',
                    'country' => 'Nigeria',
                    'postal_code' => '200001',
                ]
            );

            $institutionUser = User::query()->updateOrCreate(
                ['email' => 'institution.user@repronig.com'],
                [
                    'first_name' => 'John',
                    'last_name' => 'Registrar',
                    'phone' => '+2348000000012',
                    'password' => Hash::make('Password123!'),
                    'account_type' => 'institution_user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $institutionUser->syncRoles(['institution_user']);

            $institution = Institution::query()->updateOrCreate(
                ['email' => 'registry@seededuniversity.edu.ng'],
                [
                    'name' => 'Seeded University',
                    'licence_id' => 'RL-2601010001AA',
                    'institution_type' => 'university',
                    'registration_number' => 'REG-SU-2026-0001',
                    'phone' => '+2348000000099',
                    'contact_person_name' => 'John Registrar',
                    'contact_person_title' => 'Registrar',
                    'onboarding_status' => 'approved',
                    'account_status' => 'active',
                    'governance_status' => 'normal',
                    'address_line_1' => '15 University Road',
                    'address_line_2' => 'Main Campus',
                    'city' => 'Ibadan',
                    'state' => 'Oyo',
                    'country' => 'Nigeria',
                    'postal_code' => '200001',
                    'licensing_terms_accepted_at' => now(),
                    'licensing_terms_acknowledged_on' => now()->toDateString(),
                    'licensing_terms_version_accepted' => PlatformSettingsSeeder::DEMO_INSTITUTION_LICENSING_TERMS_VERSION,
                ]
            );

            InstitutionUser::query()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'user_id' => $institutionUser->id,
                ],
                [
                    'role_label' => 'primary_contact',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            ); */

        });
    }
}
