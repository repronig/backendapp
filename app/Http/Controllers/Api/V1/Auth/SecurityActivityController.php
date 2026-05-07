<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SecurityActivityController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();

        $logs = AuditLog::query()
            ->where('actor_user_id', $user->id)
            ->whereIn('action', [
                'user_logged_in',
                'user_logged_in_with_two_factor',
                'current_user_password_changed',
                'current_user_profile_updated',
                'password_reset_completed',
                'email_verified',
                'two_factor_enabled',
                'two_factor_disabled',
                'sensitive_action_confirmed',
            ])
            ->latest('created_at')
            ->limit(12)
            ->get();

        $items = $logs
            ->map(function (AuditLog $log) use ($currentIp, $currentUserAgent, $logs) {
                $deviceLabel = $this->deviceLabel($log->user_agent);
                $browserName = $this->browserName($log->user_agent);
                $operatingSystem = $this->operatingSystem($log->user_agent);
                $deviceType = $this->deviceType($log->user_agent);
                $isCurrentContext = $this->isCurrentContext($log->ip_address, $log->user_agent, $currentIp, $currentUserAgent);
                $anomaly = $this->anomalyFor($log, $logs, $currentIp, $currentUserAgent);

                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'label' => $this->labelFor($log->action),
                    'category' => $this->categoryFor($log->action),
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'device_label' => $deviceLabel,
                    'browser_name' => $browserName,
                    'operating_system' => $operatingSystem,
                    'device_type' => $deviceType,
                    'is_current_context' => $isCurrentContext,
                    'anomaly_level' => $anomaly['level'],
                    'anomaly_message' => $anomaly['message'],
                    'created_at' => optional($log->created_at)->toIso8601String(),
                ];
            })
            ->values();

        return $this->success('Security activity retrieved successfully.', [
            'summary' => $this->buildSummary($user, $logs, $currentIp, $currentUserAgent),
            'items' => $items->all(),
        ]);
    }

    protected function buildSummary($user, Collection $logs, ?string $currentIp = null, ?string $currentUserAgent = null): array
    {
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [];
        $hasPrivilegedAccess = in_array('admin', $roles, true) || in_array('super_admin', $roles, true);

        $recentDeviceLabels = $logs
            ->map(fn (AuditLog $log) => $this->deviceLabel($log->user_agent))
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();

        $signInLogs = $logs->filter(fn (AuditLog $log) => in_array($log->action, ['user_logged_in', 'user_logged_in_with_two_factor'], true))->values();
        $signInIps = $signInLogs->pluck('ip_address')->filter()->unique()->values();
        $signInDevices = $signInLogs->map(fn (AuditLog $log) => $this->deviceLabel($log->user_agent))->filter()->unique()->values();
        $recentAnomalyCount = $signInLogs->filter(function (AuditLog $log) use ($signInLogs, $currentIp, $currentUserAgent, $signInIps, $signInDevices) {
            return $this->anomalyFor($log, $signInLogs, $currentIp, $currentUserAgent, $signInIps->all(), $signInDevices->all())['level'] !== 'none';
        })->count();
        $lastUnfamiliarSignIn = $signInLogs->first(function (AuditLog $log) use ($signInLogs, $currentIp, $currentUserAgent, $signInIps, $signInDevices) {
            return $this->anomalyFor($log, $signInLogs, $currentIp, $currentUserAgent, $signInIps->all(), $signInDevices->all())['level'] !== 'none';
        });

        return [
            'total_events' => $logs->count(),
            'two_factor_enabled' => (bool) $user->requires_two_factor,
            'email_verified' => $user->email_verified_at !== null,
            'privileged_access' => $hasPrivilegedAccess,
            'role_scope' => $hasPrivilegedAccess ? (in_array('super_admin', $roles, true) ? 'super_admin' : 'admin') : ($roles[0] ?? 'user'),
            'login_event_count' => $signInLogs->count(),
            'protected_action_count' => $logs->where('action', 'sensitive_action_confirmed')->count(),
            'recent_anomaly_count' => $recentAnomalyCount,
            'last_unfamiliar_sign_in_at' => optional($lastUnfamiliarSignIn?->created_at)->toIso8601String(),
            'last_login_at' => optional($signInLogs->first()?->created_at)->toIso8601String(),
            'last_password_change_at' => optional($logs->first(fn (AuditLog $log) => in_array($log->action, ['current_user_password_changed', 'password_reset_completed'], true))?->created_at)->toIso8601String(),
            'last_two_factor_event_at' => optional($logs->first(fn (AuditLog $log) => in_array($log->action, ['user_logged_in_with_two_factor', 'two_factor_enabled', 'two_factor_disabled'], true))?->created_at)->toIso8601String(),
            'last_profile_update_at' => optional($logs->first(fn (AuditLog $log) => $log->action === 'current_user_profile_updated')?->created_at)->toIso8601String(),
            'last_security_confirmation_at' => optional($user->last_security_confirmation_at)->toIso8601String(),
            'last_sensitive_action_at' => optional($logs->first(fn (AuditLog $log) => $log->action === 'sensitive_action_confirmed')?->created_at)->toIso8601String(),
            'last_login_ip' => $signInLogs->first()?->ip_address,
            'current_ip' => $currentIp,
            'current_device_label' => $this->deviceLabel($currentUserAgent),
            'current_browser_name' => $this->browserName($currentUserAgent),
            'current_operating_system' => $this->operatingSystem($currentUserAgent),
            'current_device_type' => $this->deviceType($currentUserAgent),
            'recent_device_labels' => $recentDeviceLabels,
        ];
    }



    protected function anomalyFor(AuditLog $log, Collection $logs, ?string $currentIp = null, ?string $currentUserAgent = null, ?array $knownIps = null, ?array $knownDevices = null): array
    {
        if (! in_array($log->action, ['user_logged_in', 'user_logged_in_with_two_factor'], true)) {
            return ['level' => 'none', 'message' => null];
        }

        $knownIps = $knownIps ?? $logs->pluck('ip_address')->filter()->unique()->values()->all();
        $knownDevices = $knownDevices ?? $logs->map(fn (AuditLog $entry) => $this->deviceLabel($entry->user_agent))->filter()->unique()->values()->all();

        $isCurrentContext = $this->isCurrentContext($log->ip_address, $log->user_agent, $currentIp, $currentUserAgent);
        $ipSeenMultipleTimes = $log->ip_address ? count(array_keys($knownIps, $log->ip_address, true)) > 0 : false;
        $deviceLabel = $this->deviceLabel($log->user_agent);
        $deviceSeenMultipleTimes = count(array_keys($knownDevices, $deviceLabel, true)) > 0;

        if ($isCurrentContext) {
            return ['level' => 'none', 'message' => 'This matches your current signed-in session.'];
        }

        if (! $ipSeenMultipleTimes && ! $deviceSeenMultipleTimes) {
            return ['level' => 'warning', 'message' => 'This sign-in looks unfamiliar compared with your recent access history.'];
        }

        if (! $ipSeenMultipleTimes) {
            return ['level' => 'notice', 'message' => 'This sign-in used a different network or IP than your recent activity.'];
        }

        if (! $deviceSeenMultipleTimes) {
            return ['level' => 'notice', 'message' => 'This sign-in came from a device or browser not seen in your recent activity.'];
        }

        return ['level' => 'none', 'message' => null];
    }

    protected function categoryFor(string $action): string
    {
        return match ($action) {
            'user_logged_in', 'user_logged_in_with_two_factor' => 'sign_in',
            'current_user_password_changed', 'password_reset_completed' => 'credential',
            'two_factor_enabled', 'two_factor_disabled' => 'two_factor',
            'sensitive_action_confirmed' => 'protected_action',
            'email_verified' => 'identity',
            default => 'account',
        };
    }

    protected function labelFor(string $action): string
    {
        return match ($action) {
            'user_logged_in' => 'Signed in',
            'user_logged_in_with_two_factor' => 'Signed in with two-factor authentication',
            'current_user_password_changed' => 'Password changed',
            'current_user_profile_updated' => 'Profile updated',
            'password_reset_completed' => 'Password reset completed',
            'email_verified' => 'Email verified',
            'two_factor_enabled' => 'Two-factor authentication enabled',
            'two_factor_disabled' => 'Two-factor authentication disabled',
            'sensitive_action_confirmed' => 'Sensitive action confirmed',
            default => str_replace('_', ' ', $action),
        };
    }



    protected function isCurrentContext(?string $ipAddress, ?string $userAgent, ?string $currentIp, ?string $currentUserAgent): bool
    {
        if (! $currentIp && ! $currentUserAgent) {
            return false;
        }

        return $ipAddress === $currentIp && (string) $userAgent === (string) $currentUserAgent;
    }

    protected function deviceType(?string $userAgent): string
    {
        $agent = strtolower((string) $userAgent);

        if ($agent === '') {
            return 'unknown';
        }

        return str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')
            ? 'mobile'
            : 'desktop';
    }

    protected function browserName(?string $userAgent): ?string
    {
        $agent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'chrome/') => 'Chrome',
            str_contains($agent, 'safari/') && ! str_contains($agent, 'chrome/') => 'Safari',
            str_contains($agent, 'firefox/') => 'Firefox',
            default => null,
        };
    }

    protected function operatingSystem(?string $userAgent): ?string
    {
        $agent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'mac os') || str_contains($agent, 'macintosh') => 'macOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'ios') => 'iOS',
            str_contains($agent, 'linux') => 'Linux',
            default => null,
        };
    }

    protected function deviceLabel(?string $userAgent): string
    {
        $agent = strtolower((string) $userAgent);

        if ($agent === '') {
            return 'Unknown device';
        }

        $device = str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')
            ? 'Mobile device'
            : 'Desktop browser';

        $browser = match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'chrome/') => 'Chrome',
            str_contains($agent, 'safari/') && ! str_contains($agent, 'chrome/') => 'Safari',
            str_contains($agent, 'firefox/') => 'Firefox',
            default => null,
        };

        return $browser ? sprintf('%s · %s', $device, $browser) : $device;
    }
}
