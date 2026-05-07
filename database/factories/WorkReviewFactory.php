<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkReview;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkReviewFactory extends Factory
{
    protected $model = WorkReview::class;

    public function definition(): array
    {
        $decision = fake()->randomElement([
            'approved',
            'rejected',
            'changes_requested',
        ]);

        $reasonCode = match ($decision) {
            'approved' => null,
            'rejected' => fake()->randomElement([
                'invalid_metadata',
                'copyright_issue',
                'duplicate_detected',
                'incomplete_submission',
            ]),
            'changes_requested' => fake()->randomElement([
                'missing_documents',
                'metadata_correction',
                'ownership_clarification',
            ]),
        };

        return [
            'work_id' => Work::factory(),
            'reviewer_user_id' => User::factory(),
            'decision' => $decision,
            'reason_code' => $reasonCode,
            'review_note' => fake()->sentence(),
            'evidence_requested' => $decision === 'changes_requested',
            'reviewed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'metadata_json' => [
                'review_source' => fake()->randomElement(['association', 'admin']),
                'confidence_score' => fake()->randomFloat(2, 0.50, 1.00),
            ],
        ];
    }
}