<?php

namespace App\Actions\Imports;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Enums\WorkVerificationStatus;
use App\Models\Member;
use App\Models\User;
use App\Models\Work;
use InvalidArgumentException;

class ImportWorkAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(Member $member, array $data, ?User $actor = null, ?string $ipAddress = null, ?string $userAgent = null): Work
    {
        $workStatus = (string) ($data['work_status'] ?? WorkStatus::Draft->value);
        $verificationStatus = (string) ($data['verification_status'] ?? WorkVerificationStatus::Pending->value);

        if (! in_array($workStatus, WorkStatus::values(), true)) {
            throw new InvalidArgumentException('Invalid work_status supplied for import.');
        }

        if (! in_array($verificationStatus, WorkVerificationStatus::values(), true)) {
            throw new InvalidArgumentException('Invalid verification_status supplied for import.');
        }

        $typeOfWork = (string) ($data['type_of_work'] ?? '');
        if (! in_array($typeOfWork, WorkType::values(), true)) {
            throw new InvalidArgumentException('Invalid type_of_work supplied for import.');
        }

        $existing = Work::query()->where([
            'member_id' => $member->id,
            'identifier_type' => $data['identifier_type'] ?? 'other',
            'identifier_value' => $data['identifier_value'] ?? (string) ($data['title'] ?? ''),
        ])->first();

        $before = $existing?->toArray();

        $work = Work::query()->updateOrCreate(
            [
                'member_id' => $member->id,
                'identifier_type' => $data['identifier_type'] ?? 'other',
                'identifier_value' => $data['identifier_value'] ?? (string) ($data['title'] ?? ''),
            ],
            [
                'title' => (string) $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'type_of_work' => $typeOfWork,
                'publication_year' => $data['publication_year'] ?? null,
                'synopsis' => $data['synopsis'] ?? null,
                'publisher_name' => $data['publisher_name'] ?? null,
                'work_status' => $workStatus,
                'verification_status' => $verificationStatus,
            ]
        );

        $fresh = $work->fresh(['member.user']);

        $this->logAuditAction->execute(
            $actor,
            $before ? 'work_imported_updated' : 'work_imported_created',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
