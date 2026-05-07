<?php

namespace App\Actions\Dashboard;

use App\Models\Invoice;
use App\Models\Institution;
use App\Models\Licence;
use App\Models\Member;
use App\Models\Work;

class BuildBoardKpiSummaryAction
{
    public function execute(): array
    {
        $totalInvoices = (float) Invoice::query()->sum('total_amount');
        $paidInvoices = (float) Invoice::query()->sum('amount_paid');

        return [
            'total_members' => Member::query()->count(),
            'approved_members' => Member::query()->where('approval_status', 'approved')->count(),
            'pending_members' => Member::query()->whereIn('approval_status', ['pending', 'under_review', 'changes_requested'])->count(),
            'works_registered' => Work::query()->count(),
            'verified_works' => Work::query()->where('verification_status', 'verified')->count(),
            'institutions_onboarded' => Institution::query()->where('onboarding_status', 'approved')->count(),
            'licences_issued' => Licence::query()->count(),
            'invoice_collection_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0.0,
            'outstanding_receivables' => (float) Invoice::query()->sum('outstanding_amount'),
            'period' => now()->format('Y'),
        ];
    }
}
