<?php

namespace App\Actions\Reports;

use App\Models\Work;

class BuildAdminWorkReportAction
{
    public function execute(): array
    {
        return [
            'summary' => [
                'total_works' => Work::count(),
                'draft_works' => Work::where('work_status', 'draft')->count(),
                'submitted_works' => Work::where('work_status', 'submitted')->count(),
                'verified_works' => Work::where('verification_status', 'verified')->count(),
                'rejected_works' => Work::where('verification_status', 'rejected')->count(),
            ],
        ];
    }
}
