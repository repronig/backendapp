<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LogAuditAction
{
    public function execute(
        ?User $actor,
        string $action,
        Model $subject,
        ?array $before = null,
        ?array $after = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'before_json' => $before,
            'after_json' => $after,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}