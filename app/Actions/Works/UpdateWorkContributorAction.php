<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkContributor;
use Illuminate\Validation\ValidationException;

class UpdateWorkContributorAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        WorkContributor $contributor,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): WorkContributor {
        $before = $contributor->toArray();

        if (array_key_exists('ownership_percentage', $data)) {
            $this->ensureOwnershipDoesNotExceedHundred($contributor, (float) $data['ownership_percentage']);
        }

        $contributor->update($data);

        $fresh = $contributor->fresh(['disputedBy']);

        $this->logAuditAction->execute(
            $actor,
            'work_contributor_updated',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }

    private function ensureOwnershipDoesNotExceedHundred(WorkContributor $contributor, float $incomingPercentage): void
    {
        /** @var Work $work */
        $work = $contributor->work()->firstOrFail();

        $existingTotalExcludingCurrent = (float) $work->contributors()
            ->whereKeyNot($contributor->getKey())
            ->sum('ownership_percentage');

        if (($existingTotalExcludingCurrent + $incomingPercentage) > 100.0) {
            throw ValidationException::withMessages([
                'ownership_percentage' => 'Contributor ownership percentage cannot exceed 100%.',
            ]);
        }
    }
}
