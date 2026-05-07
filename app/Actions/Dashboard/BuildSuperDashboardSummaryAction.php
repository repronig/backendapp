<?php

namespace App\Actions\Dashboard;

use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Invoice;
use App\Models\LicencePayment;
use App\Models\Member;
use App\Models\User;
use App\Support\DashboardPayload;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BuildSuperDashboardSummaryAction
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
            'organizations' => [
                'institutions' => Institution::query()->count(),
                'active_institutions' => Institution::query()->where('account_status', 'active')->count(),
            ],
            'members' => [
                'total' => Member::query()->count(),
                'approved' => Member::query()->where('approval_status', 'approved')->count(),
            ],
            'invoices' => [
                'total' => Invoice::query()->count(),
                'total_amount_sum' => (float) (Invoice::query()->sum('total_amount') ?? 0),
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
            'roles' => [
                'total' => Role::query()->count(),
                'permissions' => Permission::query()->count(),
            ],
            'revenue' => [
                'total_paid_amount' => (float) LicencePayment::query()->where('payment_status', 'paid')->sum('amount'),
                'paid_payments' => LicencePayment::query()->where('payment_status', 'paid')->count(),
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
