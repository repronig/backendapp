<?php

namespace App\Actions\Reports;

use App\Models\Institution;
use App\Models\Licence;
use App\Models\UsageDeclaration;

class BuildAdminCompletenessReportAction
{
    public function execute(): array
    {
        return [
            'institutions' => [
                'with_profile' => Institution::whereHas('profile')->count(),
                'without_profile' => Institution::whereDoesntHave('profile')->count(),
            ],
            'licences' => [
                'total_licences' => Licence::count(),
                'active_licences' => Licence::where('licence_status', 'active')->count(),
                'pending_payment_licences' => Licence::where('licence_status', 'pending_payment')->count(),
                'submitted_usage_declarations' => UsageDeclaration::where('declaration_status', 'submitted')->count(),
            ],
        ];
    }
}
