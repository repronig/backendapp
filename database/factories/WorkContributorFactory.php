<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Work;
use App\Models\WorkContributor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkContributorFactory extends Factory
{
    protected $model = WorkContributor::class;

    public function definition(): array
    {
        return [
            'external_id' => (string) Str::uuid(),
            'work_id' => Work::factory(),
            'member_id' => null,
            'contributor_name' => fake()->name(),
            'contributor_role' => fake()->randomElement(['author', 'co_author', 'editor', 'translator']),
            'right_type' => fake()->randomElement(['exclusive', 'non_exclusive', 'shared']),
            'ownership_percentage' => 100,
            'is_disputed' => false,
            'dispute_reason_code' => null,
            'dispute_reason' => null,
            'disputed_by_user_id' => null,
            'disputed_at' => null,
            'territory_scope' => fake()->randomElement(['nigeria', 'africa', 'worldwide']),
        ];
    }

    public function forMember(Member $member): static
    {
        return $this->state(fn () => [
            'member_id' => $member->id,
            'contributor_name' => $member->user?->name ?? fake()->name(),
        ]);
    }
}
