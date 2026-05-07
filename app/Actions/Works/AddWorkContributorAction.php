<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkContributor;
use Illuminate\Validation\ValidationException;

class AddWorkContributorAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        Work $work,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): WorkContributor {
        $this->ensureOwnershipDoesNotExceedHundred($work, (float) ($data['ownership_percentage'] ?? 0));

        $contributor = WorkContributor::create($data + [
            'work_id' => $work->id,
        ]);

        $fresh = $contributor->fresh(['disputedBy']);

        $this->logAuditAction->execute(
            $actor,
            'work_contributor_added',
            $fresh,
            null,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }

    private function ensureOwnershipDoesNotExceedHundred(Work $work, float $incomingPercentage): void
    {
        $existingTotal = (float) $work->contributors()->sum('ownership_percentage');

        if (($existingTotal + $incomingPercentage) > 100.0) {
            throw ValidationException::withMessages([
                'ownership_percentage' => 'Contributor ownership percentage cannot exceed 100%.',
            ]);
        }
    }
}
