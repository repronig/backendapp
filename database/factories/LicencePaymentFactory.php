<?php

namespace Database\Factories;

use App\Models\Licence;
use App\Models\LicencePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicencePaymentFactory extends Factory
{
    protected $model = LicencePayment::class;

    public function definition(): array
    {
        $licence = Licence::factory()->forDeclaration()->create();
        $amount = fake()->randomFloat(2, 10000, (float) max($licence->outstanding_amount, 10000));

        return [
            'licence_id' => $licence->id,
            'institution_id' => $licence->institution_id,
            'institution_annual_declaration_id' => $licence->institution_annual_declaration_id,
            'invoice_id' => null,
            'payment_reference' => 'PAY-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'gateway_reference' => fake()->optional()->bothify('GATEWAY-########'),
            'provider_event_id' => fake()->optional(0.4)->uuid(),
            'gateway_name' => 'paystack',
            'amount' => $amount,
            'amount_allocated' => 0,
            'balance_before' => $licence->outstanding_amount,
            'balance_after' => $licence->outstanding_amount,
            'currency' => 'NGN',
            'payment_status' => fake()->randomElement([
                'pending',
                'processing',
                'paid',
                'failed',
                'cancelled',
                'pending_offline',
            ]),
            'paid_at' => null,
            'raw_response_json' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 10000, 5000000);
            $balanceBefore = $attributes['balance_before'] ?? $amount;

            return [
                'payment_status' => 'paid',
                'amount' => $amount,
                'amount_allocated' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => max((float) $balanceBefore - (float) $amount, 0),
                'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ];
        });
    }
}
