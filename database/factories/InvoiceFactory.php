<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Licence;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $licence = Licence::factory()->forDeclaration()->create();
        $subtotal = (float) ($licence->amount_due ?: fake()->randomFloat(2, 10000, 5000000));

        $status = fake()->randomElement([
            'issued',
            'partially_paid',
            'paid',
            'overdue',
            'cancelled',
        ]);

        $amountPaid = match ($status) {
            'paid' => $subtotal,
            'partially_paid' => round($subtotal * fake()->randomFloat(2, 0.2, 0.8), 2),
            default => 0,
        };

        return [
            'invoice_number' => 'INV-' . now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'institution_id' => $licence->institution_id,
            'institution_annual_declaration_id' => $licence->institution_annual_declaration_id,
            'licence_id' => $licence->id,
            'invoice_type' => fake()->randomElement([
                'licence_fee',
                'adjustment',
                'manual_invoice',
            ]),
            'billing_year' => $licence->licence_year,
            'issue_date' => fake()->dateTimeBetween('-90 days', '-1 day')->format('Y-m-d'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
            'amount_paid' => $amountPaid,
            'outstanding_amount' => max($subtotal - $amountPaid, 0),
            'invoice_status' => $status,
            'currency' => 'NGN',
            'metadata_json' => [
                'generated_by' => fake()->randomElement(['system', 'admin']),
            ],
            'issued_at' => fake()->dateTimeBetween('-90 days', '-1 day'),
            'paid_at' => $status === 'paid'
                ? fake()->dateTimeBetween('-30 days', 'now')
                : null,
            'last_due_reminder_sent_at' => in_array($status, ['issued', 'partially_paid', 'overdue'], true) && fake()->boolean(40)
                ? fake()->dateTimeBetween('-20 days', 'now')
                : null,
            'last_overdue_reminder_sent_at' => $status === 'overdue'
                ? fake()->dateTimeBetween('-10 days', 'now')
                : null,
        ];
    }
}
