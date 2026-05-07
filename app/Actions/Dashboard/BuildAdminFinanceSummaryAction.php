<?php

namespace App\Actions\Dashboard;

use App\Models\LicencePayment;

class BuildAdminFinanceSummaryAction
{
    public function execute(): array
    {
        $base = LicencePayment::query();

        $totalAmount = (float) (clone $base)->sum('amount');
        $paidAmount = (float) (clone $base)->where('payment_status', 'paid')->sum('amount');
        $pendingAmount = max($totalAmount - $paidAmount, 0);

        $recentPayments = LicencePayment::query()
            ->with(['institution'])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (LicencePayment $payment) => [
                'id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'payment_status' => $payment->payment_status,
                'created_at' => optional($payment->created_at)?->toIso8601String(),
                'paid_at' => optional($payment->paid_at)?->toIso8601String(),
                'institution' => $payment->institution ? [
                    'id' => $payment->institution->id,
                    'name' => $payment->institution->name,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'totals' => [
                'total_payments' => (clone $base)->count(),
                'total_amount' => $totalAmount,
                'total_paid_amount' => $paidAmount,
                'total_pending_amount' => $pendingAmount,
            ],
            'status_breakdown' => [
                'paid' => (clone $base)->where('payment_status', 'paid')->count(),
                'pending' => (clone $base)->where('payment_status', 'pending')->count(),
                'failed' => (clone $base)->where('payment_status', 'failed')->count(),
            ],
            'recent_payments' => $recentPayments,
        ];
    }
}
