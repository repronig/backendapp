<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Licence;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenceFactory extends Factory
{
    protected $model = Licence::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'institution_annual_declaration_id' => null,
            'licence_number' => 'LIC-' . now()->format('Y') . '-' . fake()->unique()->numerify('####'),
            'licence_id_snapshot' => null,
            'licence_year' => (int) now()->format('Y'),
            'agreement_version' => 'v1',
            'licence_status' => 'draft',
            'payment_status' => 'pending',
            'start_date' => null,
            'end_date' => null,
            'negotiated_rate' => null,
            'amount_due' => 0,
            'amount_paid' => 0,
            'outstanding_amount' => 0,
            'issued_by_user_id' => null,
            'issued_at' => null,
        ];
    }

    public function forDeclaration(?InstitutionAnnualDeclaration $declaration = null): static
    {
        return $this->state(function () use ($declaration) {
            $declaration ??= InstitutionAnnualDeclaration::factory()->approved()->create();

            return [
                'institution_id' => $declaration->institution_id,
                'institution_annual_declaration_id' => $declaration->id,
                'licence_id_snapshot' => $declaration->licence_id_snapshot,
                'licence_year' => $declaration->licensing_year,
                'amount_due' => $declaration->expected_amount,
                'amount_paid' => $declaration->paid_amount,
                'outstanding_amount' => $declaration->outstanding_amount,
                'licence_number' => sprintf('%s-%s', $declaration->licence_id_snapshot ?: 'LIC', $declaration->licensing_year),
                'licence_status' => (float) $declaration->outstanding_amount > 0 ? 'pending_payment' : 'active',
                'payment_status' => (float) $declaration->paid_amount <= 0 ? 'pending' : ((float) $declaration->outstanding_amount > 0 ? 'partially_paid' : 'paid'),
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
            ];
        });
    }
}
