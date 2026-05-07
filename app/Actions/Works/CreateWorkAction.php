<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Models\Member;
use App\Models\User;
use App\Models\Work;
use Illuminate\Validation\ValidationException;

class CreateWorkAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected FindDuplicateWorksAction $findDuplicateWorksAction,
    ) {}

    public function execute(
        Member $member,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Work {
        if (Work::shouldEnforceIdentifierUniqueness($data)) {
            $identifierType = strtolower((string) ($data['identifier_type'] ?? ''));
            $duplicates = $this->findDuplicateWorksAction->execute($data);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'identifier_value' => ['A work with this '.$identifierType.' identifier already exists.'],
                ]);
            }
        }

        $work = Work::create($data + [
            'member_id' => $member->id,
            'work_status' => WorkStatus::Draft->value,
            'verification_status' => WorkVerificationStatus::Pending->value,
        ]);

        $duplicates = $this->findDuplicateWorksAction->execute($work->toArray(), $work->id);
        if ($duplicates->isNotEmpty()) {
            $work->forceFill([
                'is_disputed' => true,
                'review_reason' => 'Potential duplicate detected during draft creation.',
            ])->save();
        }

        $fresh = $work->fresh(['contributors.disputedBy', 'files', 'member', 'reviews', 'verifier', 'lastReviewer']);

        $this->logAuditAction->execute(
            $actor,
            'work_created',
            $fresh,
            null,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
