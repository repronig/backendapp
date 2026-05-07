<?php

namespace Database\Factories;

use App\Enums\WorkFormat;
use App\Enums\WorkIdentifierType;
use App\Enums\WorkProductionStatus;
use App\Enums\WorkStatus;
use App\Enums\WorkTargetMarket;
use App\Enums\WorkType;
use App\Enums\WorkVerificationStatus;
use App\Models\Member;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition(): array
    {
        $typeOfWork = fake()->randomElement(WorkType::values());
        $targetMarket = fake()->randomElement(WorkTargetMarket::values());

        return [
            'member_id' => Member::factory(),
            'reference_number' => 'WRK-' . now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'external_id' => (string) Str::uuid(),
            'type_of_work' => $typeOfWork,
            'title' => fake()->sentence(4),
            'subtitle' => fake()->optional()->sentence(3),
            'publication_year' => (int) fake()->year(),
            'synopsis' => fake()->paragraph(),
            'primary_language' => 'English',
            'work_format' => fake()->randomElement(WorkFormat::values()),
            'identifier_type' => fake()->randomElement(WorkIdentifierType::values()),
            'identifier_value' => fake()->unique()->isbn13(),
            'duplicate_fingerprint' => hash('sha256', Str::uuid()->toString()),
            'doi' => fake()->optional()->url(),
            'publisher_name' => fake()->company(),
            'target_market' => $targetMarket,
            'target_market_other' => $targetMarket === WorkTargetMarket::Other->value ? fake()->words(3, true) : null,
            'production_status' => fake()->randomElement(WorkProductionStatus::values()),
            'agreement_accepted' => true,
            'date_of_consent' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'other_work_type' => $typeOfWork === WorkType::Other->value ? fake()->words(3, true) : null,
            'notes' => fake()->optional()->paragraph(),
            'work_status' => fake()->randomElement(WorkStatus::values()),
            'verification_status' => fake()->randomElement(WorkVerificationStatus::values()),
            'submitted_at' => null,
            'verified_at' => null,
            'verified_by_user_id' => null,
            'last_reviewed_by_user_id' => null,
            'last_reviewed_at' => null,
            'review_reason' => null,
            'is_disputed' => false,
            'is_restricted' => false,
            'governance_reason_code' => null,
            'governance_reason' => null,
        ];
    }
}
