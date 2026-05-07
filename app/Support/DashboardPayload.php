<?php

namespace App\Support;

use App\Models\AuditLog;

/**
 * Shared shape for portal dashboard JSON (Pass E backend v2): meta + audit rows.
 */
final class DashboardPayload
{
    public const SCHEMA_VERSION = 2;

    /** Default row count for dashboard “recent activity” blocks across portals. */
    public const RECENT_ACTIVITY_LIMIT = 8;

    /**
     * @return array{schema_version: int, generated_at: string}
     */
    public static function meta(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public static function normalizeSubjectType(?string $subjectType): ?string
    {
        if ($subjectType === null || $subjectType === '') {
            return null;
        }

        return str_contains($subjectType, '\\')
            ? class_basename($subjectType)
            : $subjectType;
    }

    /**
     * @return array{id: int, action: string, subject_type: string|null, subject_id: int|null, created_at: string|null, actor: array{id: int, name: string, email: string|null}|null}
     */
    public static function serializeAuditLog(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'subject_type' => self::normalizeSubjectType($log->subject_type),
            'subject_id' => $log->subject_id,
            'created_at' => optional($log->created_at)?->toIso8601String(),
            'actor' => $log->actor ? [
                'id' => $log->actor->id,
                'name' => $log->actor->name,
                'email' => $log->actor->email,
            ] : null,
        ];
    }
}
