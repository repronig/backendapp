<?php

namespace Database\Seeders;

use App\Actions\AssociationReview\ApproveMemberApplicationAction;
use App\Actions\AssociationReview\RejectMemberApplicationAction;
use App\Actions\AssociationReview\RequestChangesMemberApplicationAction;
use App\Actions\Licensing\ApproveInstitutionAnnualDeclarationAction;
use App\Actions\Licensing\CreateInstitutionAnnualDeclarationAction;
use App\Actions\Licensing\GenerateInstitutionInvoiceAction;
use App\Actions\Licensing\MoveInstitutionAnnualDeclarationToReviewAction;
use App\Actions\Licensing\RejectInstitutionAnnualDeclarationAction;
use App\Actions\Licensing\SubmitInstitutionAnnualDeclarationAction;
use App\Actions\MemberOnboarding\SubmitMemberApplicationAction;
use App\Actions\WorkReviews\ReviewWorkAction;
use App\Actions\Works\AddWorkContributorAction;
use App\Actions\Works\CreateWorkAction;
use App\Actions\Works\SubmitWorkAction;
use App\Actions\Works\UploadWorkFileAction;
use App\Enums\MemberAuthorCategory;
use App\Enums\MemberAuthorType;
use App\Enums\WorkFormat;
use App\Enums\WorkIdentifierType;
use App\Enums\WorkProductionStatus;
use App\Enums\WorkTargetMarket;
use App\Enums\WorkType;
use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionDocument;
use App\Models\InstitutionProfile;
use App\Models\InstitutionUser;
use App\Models\InvoiceAdjustment;
use App\Models\LicencePayment;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\MemberProfile;
use App\Models\SavedReportingPeriod;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        /*
        DB::transaction(function (): void {

            $associations = Association::query()->count() > 0
                ? Association::query()->get()
                : Association::factory()->count(6)->create();

            foreach ($associations as $association) {
                User::factory()
                    ->count(1)
                    ->create(['account_type' => 'association_officer'])
                    ->each(function (User $user) use ($association): void {
                        $user->syncRoles(['association_officer']);
                        $user->associations()->syncWithoutDetaching([
                            $association->id => [
                                'designation_title' => fake()->jobTitle(),
                                'is_active' => true,
                            ],
                        ]);
                    });
            }

            $members = collect();

            foreach ($associations as $association) {
                User::factory()
                    ->count(40)
                    ->create(['account_type' => 'member'])
                    ->each(function (User $user) use ($association, &$members): void {
                        $user->syncRoles(['member']);
                        $reviewer = $this->resolveReviewer($association);
                        $application = $this->createDraftMemberApplication($user, $association);

                        $targetOutcome = fake()->randomElement([
                            'draft',
                            'submitted',
                            'approved',
                            'approved',
                            'approved',
                            'rejected',
                            'changes_requested',
                        ]);

                        if ($targetOutcome === 'draft') {
                            return;
                        }

                        $submitted = app(SubmitMemberApplicationAction::class)->execute(
                            $application->fresh('documents'),
                            $user,
                            '127.0.0.1',
                            'DemoDataSeeder'
                        );

                        if ($targetOutcome === 'submitted') {
                            return;
                        }

                        if ($targetOutcome === 'rejected') {
                            app(RejectMemberApplicationAction::class)->execute(
                                $submitted,
                                $reviewer,
                                fake()->sentence(),
                                '127.0.0.1',
                                'DemoDataSeeder'
                            );

                            return;
                        }

                        if ($targetOutcome === 'changes_requested') {
                            app(RequestChangesMemberApplicationAction::class)->execute(
                                $submitted,
                                $reviewer,
                                fake()->sentence(),
                                '127.0.0.1',
                                'DemoDataSeeder'
                            );

                            return;
                        }

                        app(ApproveMemberApplicationAction::class)->execute(
                            $submitted,
                            $reviewer,
                            fake()->optional()->sentence(),
                            '127.0.0.1',
                            'DemoDataSeeder'
                        );

                        $member = Member::query()
                            ->where('user_id', $user->id)
                            ->where('association_id', $association->id)
                            ->first();

                        if (! $member) {
                            return;
                        }

                        $member->update([
                            'account_status' => fake()->randomElement(['active', 'active', 'active', 'suspended']),
                        ]);

                        MemberProfile::query()->firstOrCreate(
                            ['member_id' => $member->id],
                            MemberProfile::factory()->make(['member_id' => $member->id])->toArray()
                        );

                        $members->push($member);
                    });
            }

            $works = collect();
            foreach ($members as $member) {
                $workCount = fake()->numberBetween(2, 5);
                for ($i = 0; $i < $workCount; $i++) {
                    $works->push($this->seedWorkLifecycle($member));
                }
            }

            $institutions = Institution::factory()->count(20)->create();

            Institution::query()->whereIn('id', $institutions->pluck('id'))->update([
                'licensing_terms_accepted_at' => now(),
                'licensing_terms_acknowledged_on' => now()->toDateString(),
                'licensing_terms_version_accepted' => PlatformSettingsSeeder::DEMO_INSTITUTION_LICENSING_TERMS_VERSION,
            ]);

            foreach ($institutions as $institution) {
                $usesAcademicMetrics = in_array($institution->institution_type, [
                    'university',
                    'polytechnic',
                    'college_of_education',
                    'research_institute',
                ], true);

                $profileFactory = InstitutionProfile::factory();

                if (! $usesAcademicMetrics) {
                    $profileFactory = $profileFactory->nonAcademic();
                }

                $profileFactory->create(['institution_id' => $institution->id]);
                $primaryUser = User::factory()->create(['account_type' => 'institution_user']);
                $primaryUser->syncRoles(['institution_user']);

                InstitutionUser::factory()->primary()->create([
                    'institution_id' => $institution->id,
                    'user_id' => $primaryUser->id,
                ]);

                if (fake()->boolean(35)) {
                    $secondaryUser = User::factory()->create(['account_type' => 'institution_user']);
                    $secondaryUser->syncRoles(['institution_user']);
                    InstitutionUser::factory()->create([
                        'institution_id' => $institution->id,
                        'user_id' => $secondaryUser->id,
                        'is_primary' => false,
                    ]);
                }

                InstitutionDocument::factory()->count(fake()->numberBetween(1, 3))->create([
                    'institution_id' => $institution->id,
                    'uploaded_by_user_id' => $primaryUser->id,
                ]);
            }

            SavedReportingPeriod::factory()->count(5)->create();

            $invoices = collect();
            foreach ($institutions as $institution) {
                $result = $this->seedInstitutionLicensingLifecycle($institution);
                if ($result['invoice']) {
                    $invoices->push($result['invoice']);
                }
            }

            foreach ($invoices as $invoice) {
                if ($invoice->invoice_status === 'cancelled') {
                    continue;
                }

                if ($invoice->invoice_status === 'paid') {
                    LicencePayment::factory()->paid()->create([
                        'licence_id' => $invoice->licence_id,
                        'institution_id' => $invoice->institution_id,
                        'institution_annual_declaration_id' => $invoice->institution_annual_declaration_id,
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->total_amount,
                        'amount_allocated' => $invoice->total_amount,
                    ]);

                    continue;
                }

                if ($invoice->invoice_status === 'partially_paid') {
                    $allocated = round((float) $invoice->total_amount * fake()->randomFloat(2, 0.2, 0.8), 2);
                    LicencePayment::factory()->paid()->create([
                        'licence_id' => $invoice->licence_id,
                        'institution_id' => $invoice->institution_id,
                        'institution_annual_declaration_id' => $invoice->institution_annual_declaration_id,
                        'invoice_id' => $invoice->id,
                        'amount' => $allocated,
                        'amount_allocated' => $allocated,
                    ]);

                    $invoice->update([
                        'amount_paid' => $allocated,
                        'outstanding_amount' => round((float) $invoice->total_amount - $allocated, 2),
                    ]);

                    continue;
                }

                LicencePayment::factory()->create([
                    'licence_id' => $invoice->licence_id,
                    'institution_id' => $invoice->institution_id,
                    'institution_annual_declaration_id' => $invoice->institution_annual_declaration_id,
                    'invoice_id' => $invoice->id,
                    'payment_status' => fake()->randomElement(['pending', 'failed']),
                    'amount_allocated' => 0,
                ]);
            }

            foreach ($invoices->random(min(10, $invoices->count())) as $invoice) {
                InvoiceAdjustment::factory()->create([
                    'invoice_id' => $invoice->id,
                    'adjustment_type' => fake()->randomElement(['credit_note', 'discount']),
                ]);
            }
        }); */
    }

    /*

    protected function resolveReviewer(Association $association): User
    {
        $reviewer = $association->users()->wherePivot('is_active', true)->inRandomOrder()->first();
        if ($reviewer instanceof User) {
            return $reviewer;
        }

        $admin = User::query()->whereIn('account_type', ['admin', 'super_admin'])->inRandomOrder()->first();
        if ($admin instanceof User) {
            return $admin;
        }

        $admin = User::factory()->create(['account_type' => 'admin']);
        $admin->syncRoles(['admin']);

        return $admin;
    }

    protected function createDraftMemberApplication(User $user, Association $association): MemberApplication
    {
        $applicantType = fake()->randomElement(['author', 'publisher']);

        $applicationData = [
            'user_id' => $user->id,
            'association_id' => $association->id,
            'applicant_type' => $applicantType,
            'application_status' => 'draft',
            'submission_stage' => 'profile_incomplete',
            'nationality' => 'Nigerian',
            'country_of_residence' => 'Nigeria',
            'is_diaspora' => false,
            'bank_name' => fake()->randomElement(['Access Bank', 'GTBank', 'Zenith Bank', 'UBA', 'First Bank']),
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_owner_name' => trim($user->first_name.' '.$user->last_name),
            'consent_accepted' => true,
            'consent_date' => now()->toDateString(),
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'member_provided_id' => fake()->boolean(25) ? strtoupper(fake()->bothify('??####??')) : null,
        ];

        if ($applicantType === 'author') {
            $applicationData += [
                'member_author_type' => fake()->randomElement(MemberAuthorType::values()),
                'member_author_category' => fake()->randomElement(MemberAuthorCategory::values()),
                'next_of_kin_name' => fake()->name(),
                'next_of_kin_phone' => fake()->phoneNumber(),
            ];
        } else {
            $applicationData += [
                'publisher_organisation_name' => fake()->company(),
                'publisher_tin' => strtoupper(fake()->bothify('TIN-####-??')),
                'publisher_location_address' => fake()->address(),
                'publisher_postal_address' => fake()->address(),
                'publisher_email' => fake()->companyEmail(),
                'publisher_phone' => fake()->phoneNumber(),
            ];
        }

        $application = MemberApplication::factory()->create($applicationData);

        foreach (['proof_of_id', 'proof_of_address'] as $index => $documentType) {
            MemberApplicationDocument::factory()->pending()->create([
                'member_application_id' => $application->id,
                'uploaded_by_user_id' => $user->id,
                'document_type' => $documentType,
                'file_name' => $documentType.'-'.($index + 1).'.pdf',
            ]);
        }

        return $application->fresh('documents');
    }

    protected function seedWorkLifecycle(Member $member): Work
    {
        $actor = $member->user;
        $work = app(CreateWorkAction::class)->execute(
            $member,
            [
                'type_of_work' => fake()->randomElement([WorkType::EducationalNonFictionScientific->value, WorkType::FictionText->value, WorkType::NewsArticlesJournalisticText->value, WorkType::SongText->value, WorkType::MusicalScore->value]),
                'title' => fake()->sentence(3),
                'subtitle' => fake()->optional()->sentence(3),
                'primary_language' => 'English',
                'work_format' => fake()->randomElement(WorkFormat::values()),
                'identifier_type' => fake()->randomElement(WorkIdentifierType::values()),
                'identifier_value' => fake()->unique()->isbn13(),
                'publication_year' => fake()->numberBetween(2018, 2025),
                'publisher_name' => fake()->company(),
                'target_market' => fake()->randomElement([WorkTargetMarket::School->value, WorkTargetMarket::Tertiary->value, WorkTargetMarket::TradeBook->value, WorkTargetMarket::GeneralPublic->value]),
                'production_status' => fake()->randomElement(WorkProductionStatus::values()),
                'agreement_accepted' => true,
                'date_of_consent' => now()->toDateString(),
                'synopsis' => fake()->paragraph(),
                'notes' => fake()->optional()->paragraph(),
            ],
            $actor,
            '127.0.0.1',
            'DemoDataSeeder'
        );

        $targetOutcome = fake()->randomElement(['draft', 'submitted', 'under_review', 'verified', 'approved', 'restricted']);
        if ($targetOutcome === 'draft') {
            return $work;
        }

        app(AddWorkContributorAction::class)->execute($work, [
            'contributor_name' => trim($actor->first_name.' '.$actor->last_name),
            'contributor_role' => 'author',
            'ownership_percentage' => 100,
            'right_type' => 'exclusive',
        ], $actor, '127.0.0.1', 'DemoDataSeeder');

        app(UploadWorkFileAction::class)->execute($work, UploadedFile::fake()->image('cover.jpg'), 'cover_image', $actor, 'public', '127.0.0.1', 'DemoDataSeeder');
        app(UploadWorkFileAction::class)->execute($work, UploadedFile::fake()->create('copyright.pdf', 120, 'application/pdf'), 'copyright_page', $actor, 'public', '127.0.0.1', 'DemoDataSeeder');

        $submitted = app(SubmitWorkAction::class)->execute($work->fresh(['contributors', 'files', 'member.user']), $actor, '127.0.0.1', 'DemoDataSeeder');
        if ($targetOutcome === 'submitted') {
            return $submitted;
        }

        $reviewer = User::query()->whereIn('account_type', ['admin', 'super_admin'])->inRandomOrder()->first();
        if (! $reviewer instanceof User) {
            $reviewer = User::factory()->create(['account_type' => 'admin']);
            $reviewer->syncRoles(['admin']);
        }

        $reviewable = $submitted->fresh();
        if ($targetOutcome === 'under_review') {
            $reviewable->update(['work_status' => 'under_review']);

            return $reviewable->fresh();
        }

        if ($targetOutcome === 'verified') {
            return app(ReviewWorkAction::class)->execute($reviewable, [
                'decision' => 'verified',
                'review_note' => fake()->sentence(),
            ], $reviewer, '127.0.0.1', 'DemoDataSeeder');
        }

        if ($targetOutcome === 'approved') {
            $verified = app(ReviewWorkAction::class)->execute($reviewable, [
                'decision' => 'verified',
                'review_note' => fake()->sentence(),
            ], $reviewer, '127.0.0.1', 'DemoDataSeeder');

            return app(ReviewWorkAction::class)->execute($verified, [
                'decision' => 'approved',
                'review_note' => fake()->sentence(),
            ], $reviewer, '127.0.0.1', 'DemoDataSeeder');
        }

        return app(ReviewWorkAction::class)->execute($reviewable, [
            'decision' => 'restricted',
            'reason_code' => fake()->randomElement(['rights_dispute', 'policy_violation', 'court_notice', 'manual_restriction']),
            'review_note' => fake()->sentence(),
        ], $reviewer, '127.0.0.1', 'DemoDataSeeder');
    }

    protected function seedInstitutionLicensingLifecycle(Institution $institution): array
    {
        $primaryUser = $institution->institutionUsers()->where('is_primary', true)->with('user')->first()?->user;
        if (! $primaryUser instanceof User) {
            return ['declaration' => null, 'invoice' => null];
        }

        $targetOutcome = fake()->randomElement(['draft', 'submitted', 'under_review', 'approved', 'rejected']);
        $academicTypes = ['university', 'polytechnic', 'college_of_education', 'research_institute'];
        $usesAcademicDeclaration = in_array($institution->institution_type, $academicTypes, true);
        $artsStudents = fake()->numberBetween(800, 4000);
        $scienceStudents = fake()->numberBetween(800, 4000);

        $data = [
            'licensing_year' => 2026,
            'supporting_document' => UploadedFile::fake()->create(
                'institution-declaration-supporting-document.pdf',
                256,
                'application/pdf'
            ),
        ];

        if ($usesAcademicDeclaration) {
            $data += [
                'declared_students_count' => $artsStudents + $scienceStudents,
                'faculties' => [
                    ['faculty_name' => 'Faculty of Arts', 'student_count' => $artsStudents],
                    ['faculty_name' => 'Faculty of Science', 'student_count' => $scienceStudents],
                ],
            ];
        } else {
            $data += [
                'declared_members_count' => max((int) ($institution->member_count ?? 0), fake()->numberBetween(100, 3000)),
                'declared_branches_count' => max((int) ($institution->branches_count ?? 0), fake()->numberBetween(1, 25)),
                'faculties' => [],
            ];
        }

        $declaration = app(CreateInstitutionAnnualDeclarationAction::class)->execute(
            $institution,
            $data,
            $primaryUser,
            '127.0.0.1',
            'DemoDataSeeder'
        );

        if ($targetOutcome === 'draft') {
            return ['declaration' => $declaration, 'invoice' => null];
        }

        $submitted = app(SubmitInstitutionAnnualDeclarationAction::class)->execute(
            $declaration->fresh(['institution', 'faculties']),
            $primaryUser,
            '127.0.0.1',
            'DemoDataSeeder'
        );

        if ($targetOutcome === 'submitted') {
            return ['declaration' => $submitted, 'invoice' => null];
        }

        $reviewer = User::query()->whereIn('account_type', ['admin', 'super_admin'])->inRandomOrder()->first();
        if (! $reviewer instanceof User) {
            $reviewer = User::factory()->create(['account_type' => 'admin']);
            $reviewer->syncRoles(['admin']);
        }

        if ($targetOutcome === 'under_review') {
            $reviewed = app(MoveInstitutionAnnualDeclarationToReviewAction::class)->execute(
                $submitted,
                $reviewer,
                fake()->sentence(),
                '127.0.0.1',
                'DemoDataSeeder'
            );

            return ['declaration' => $reviewed, 'invoice' => null];
        }

        if ($targetOutcome === 'rejected') {
            $reviewed = app(MoveInstitutionAnnualDeclarationToReviewAction::class)->execute(
                $submitted,
                $reviewer,
                fake()->sentence(),
                '127.0.0.1',
                'DemoDataSeeder'
            );

            $rejected = app(RejectInstitutionAnnualDeclarationAction::class)->execute(
                $reviewed,
                $reviewer,
                fake()->sentence(),
                '127.0.0.1',
                'DemoDataSeeder'
            );

            return ['declaration' => $rejected, 'invoice' => null];
        }

        $reviewed = app(MoveInstitutionAnnualDeclarationToReviewAction::class)->execute(
            $submitted,
            $reviewer,
            fake()->sentence(),
            '127.0.0.1',
            'DemoDataSeeder'
        );

        $approved = app(ApproveInstitutionAnnualDeclarationAction::class)->execute(
            $reviewed->fresh('institution'),
            $reviewer,
            '127.0.0.1',
            'DemoDataSeeder'
        );

        $invoice = app(GenerateInstitutionInvoiceAction::class)->execute($approved->fresh('institution'), $reviewer);

        if (fake()->boolean(30)) {
            $invoice->update(['invoice_status' => fake()->randomElement(['partially_paid', 'paid'])]);
        }

        return ['declaration' => $approved, 'invoice' => $invoice->fresh()];
    } */
}
