<?php

namespace Database\Factories;

use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberApplicationDocumentFactory extends Factory
{
    protected $model = MemberApplicationDocument::class;

    public function definition(): array
    {
        return [
            'member_application_id' => MemberApplication::factory(),
            'member_id' => null,
            'document_type' => fake()->randomElement([
                'proof_of_id',
                'proof_of_address',
            ]),
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10000, 500000),
            'verification_status' => fake()->randomElement([
                'pending',
                'verified',
                'rejected',
            ]),
            'verification_notes' => fake()->optional()->sentence(),
            'uploaded_by_user_id' => User::factory(),
            'verified_by_user_id' => null,
            'verified_at' => null,
        ];
    }

    public function forApplication(MemberApplication $application): static
    {
        return $this->state(fn () => [
            'member_application_id' => $application->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'pending',
            'verified_by_user_id' => null,
            'verified_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'verified',
            'verified_by_user_id' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'rejected',
            'verified_by_user_id' => User::factory(),
            'verified_at' => now(),
        ]);
    }
}