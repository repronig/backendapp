<?php

namespace App\Actions\Reports;

use App\Models\Member;
use App\Models\MemberApplication;

class BuildAdminMemberReportAction
{
    public function execute(): array
    {
        return [
            'summary' => [
                'total_members' => Member::count(),
                'approved_members' => Member::where('approval_status', 'approved')->count(),
                'author_members' => Member::where('member_type', 'author')->count(),
                'publisher_members' => Member::where('member_type', 'publisher')->count(),
                'corporate_publisher_members' => Member::where('member_type', 'corporate_publisher')->count(),
            ],
            'applications' => [
                'total' => MemberApplication::count(),
                'submitted' => MemberApplication::where('application_status', 'submitted')->count(),
                'approved' => MemberApplication::where('application_status', 'approved')->count(),
                'rejected' => MemberApplication::where('application_status', 'rejected')->count(),
                'changes_requested' => MemberApplication::where('application_status', 'changes_requested')->count(),
            ],
        ];
    }
}
