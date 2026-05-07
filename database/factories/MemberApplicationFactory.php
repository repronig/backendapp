<?php

namespace Database\Factories;

use App\Enums\MemberApplicationStatus;
use App\Enums\MemberAuthorCategory;
use App\Enums\MemberAuthorType;
use App\Models\Association;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MemberApplicationFactory extends Factory
{
    protected $model = MemberApplication::class;

    public function definition(): array
    {
        $status = fake()->randomElement(MemberApplicationStatus::values());
        $submittedAt = fake()->dateTimeBetween('-30 days', '-5 days');
        $reviewedAt = in_array($status, [
            MemberApplicationStatus::Approved->value,
            MemberApplicationStatus::Rejected->value,
            MemberApplicationStatus::ChangesRequested->value,
        ], true)
            ? fake()->dateTimeBetween($submittedAt, 'now')
            : null;
        $applicantType = fake()->randomElement(['author', 'publisher']);

        $base = [
            'external_id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'association_id' => Association::factory(),
            'applicant_type' => $applicantType,
            'application_status' => $status,
            'submission_stage' => $status === MemberApplicationStatus::Draft->value ? 'profile_incomplete' : 'completed',
            'nationality' => 'Nigerian',
            'country_of_residence' => 'Nigeria',
            'is_diaspora' => fake()->boolean(10),
            'bank_name' => fake()->randomElement(['Access Bank', 'GTBank', 'Zenith Bank', 'UBA', 'First Bank']),
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_owner_name' => fake()->name(),
            'consent_accepted' => true,
            'consent_date' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'notes' => fake()->optional()->sentence(),
            'member_provided_id' => fake()->optional(0.2)->bothify('??####'),
            'submitted_at' => $status === MemberApplicationStatus::Draft->value ? null : $submittedAt,
            'reviewed_at' => $reviewedAt,
            'reviewed_by_user_id' => $reviewedAt ? User::factory() : null,
        ];

        if ($applicantType === 'author') {
            return $base + [
                'member_author_type' => fake()->randomElement(MemberAuthorType::values()),
                'member_author_category' => fake()->randomElement(MemberAuthorCategory::values()),
                'next_of_kin_name' => fake()->name(),
                'next_of_kin_phone' => fake()->phoneNumber(),
                'publisher_organisation_name' => null,
                'publisher_tin' => null,
                'publisher_location_address' => null,
                'publisher_postal_address' => null,
                'publisher_email' => null,
                'publisher_phone' => null,
            ];
        }

        return $base + [
            'member_author_type' => null,
            'member_author_category' => null,
            'next_of_kin_name' => null,
            'next_of_kin_phone' => null,
            'publisher_organisation_name' => fake()->company(),
            'publisher_tin' => strtoupper(fake()->bothify('TIN-####-??')),
            'publisher_location_address' => fake()->address(),
            'publisher_postal_address' => fake()->address(),
            'publisher_email' => fake()->companyEmail(),
            'publisher_phone' => fake()->phoneNumber(),
        ];
    }
}
