<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionDocument>
 */
class InstitutionDocumentFactory extends Factory
{
    protected $model = InstitutionDocument::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'verified', 'rejected']);

        return [
            'institution_id' => Institution::factory(),
            'document_type' => fake()->randomElement([
                'cac_document',
                'accreditation_certificate',
                'ownership_proof',
                'authorization_letter',
            ]),
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->slug() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(50_000, 8_000_000),
            'verification_status' => $status,
            'uploaded_by_user_id' => User::factory(),
            'verified_by_user_id' => $status !== 'pending' ? User::factory() : null,
            'verified_at' => $status !== 'pending'
                ? fake()->dateTimeBetween('-30 days', 'now')
                : null,
        ];
    }
}