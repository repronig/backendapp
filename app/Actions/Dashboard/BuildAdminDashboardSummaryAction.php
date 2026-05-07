<?php

namespace App\Actions\Dashboard;

use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Invoice;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\UsageDeclaration;
use App\Models\User;
use App\Models\Work;
use App\Support\DashboardPayload;
use App\Support\PostgresSearch;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BuildAdminDashboardSummaryAction
{
    public function execute(): array
    {
        return [
            'meta' => DashboardPayload::meta(),
            'users' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('status', 'active')->count(),
                'inactive' => User::query()->where('status', 'inactive')->count(),
                'suspended' => User::query()->where('status', 'suspended')->count(),
                'admins' => User::query()->where('account_type', 'admin')->count(),
                'super_admins' => User::query()->where('account_type', 'super_admin')->count(),
            ],
            'associations' => [
                'total' => Association::query()->count(),
                'active' => Association::query()->where('status', 'active')->count(),
                'enabled' => Association::query()->where('is_enabled', true)->count(),
                'disabled' => Association::query()->where('is_enabled', false)->count(),
            ],
            'roles' => [
                'total' => Role::query()->count(),
                'permissions' => Permission::query()->count(),
            ],
            'members' => [
                'total' => Member::query()->count(),
                'approved' => Member::query()->where('approval_status', 'approved')->count(),
            ],
            'member_applications' => [
                'total' => MemberApplication::query()->count(),
                'submitted' => MemberApplication::query()->where('application_status', 'submitted')->count(),
                'approved' => MemberApplication::query()->where('application_status', 'approved')->count(),
                'rejected' => MemberApplication::query()->where('application_status', 'rejected')->count(),
                'changes_requested' => MemberApplication::query()->where('application_status', 'changes_requested')->count(),
            ],
            'works' => [
                'total' => Work::query()->count(),
                'draft' => Work::query()->where('work_status', 'draft')->count(),
                'submitted' => Work::query()->where('work_status', 'submitted')->count(),
                'verified' => Work::query()->where('verification_status', 'verified')->count(),
            ],
            'institutions' => [
                'total' => Institution::query()->count(),
                'pending_review' => Institution::query()->where('account_status', 'pending_review')->count(),
                'active' => Institution::query()->where('account_status', 'active')->count(),
            ],
            'licences' => [
                'total' => Licence::query()->count(),
                'active' => Licence::query()->where('licence_status', 'active')->count(),
                'pending_payment' => Licence::query()->where('licence_status', 'pending_payment')->count(),
            ],
            'payments' => [
                'total' => LicencePayment::query()->count(),
                'paid' => LicencePayment::query()->where('payment_status', 'paid')->count(),
                'pending' => LicencePayment::query()->where('payment_status', 'pending')->count(),
                'failed' => LicencePayment::query()->where('payment_status', 'failed')->count(),
                'total_paid_amount' => (float) LicencePayment::query()
                    ->where('payment_status', 'paid')
                    ->sum('amount'),
                'total_amount_sum' => (float) (LicencePayment::query()->sum('amount') ?? 0),
            ],
            'invoices' => [
                'total' => Invoice::query()->count(),
                'total_amount_sum' => (float) (Invoice::query()->sum('total_amount') ?? 0),
            ],
            'usage_declarations' => [
                'total' => UsageDeclaration::query()->count(),
                'submitted' => UsageDeclaration::query()->where('declaration_status', 'submitted')->count(),
            ],
            'audit_summary' => [
                'recent_actions' => AuditLog::query()->count(),
                'approvals' => PostgresSearch::whereColumnIlikeCompatible(AuditLog::query(), 'action', 'approve')->count(),
                'rejections' => PostgresSearch::whereColumnIlikeCompatible(AuditLog::query(), 'action', 'reject')->count(),
                'deactivations' => PostgresSearch::whereColumnIlikeCompatible(AuditLog::query(), 'action', 'deactivat')->count(),
            ],
            'recent_activity' => AuditLog::query()
                ->with('actor')
                ->latest('created_at')
                ->limit(DashboardPayload::RECENT_ACTIVITY_LIMIT)
                ->get()
                ->map(fn (AuditLog $log) => DashboardPayload::serializeAuditLog($log))
                ->values()
                ->all(),
        ];
    }
}
