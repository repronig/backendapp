<?php

namespace App\Actions\MemberWorkImports;

use App\Actions\Works\AddWorkContributorAction;
use App\Actions\Works\CreateWorkAction;
use App\Models\ImportBatch;
use App\Models\Member;
use App\Models\MemberWorkImportItem;
use App\Models\User;

class ImportMemberWorkRowAction
{
    public function __construct(
        protected CreateWorkAction $createWorkAction,
        protected AddWorkContributorAction $addWorkContributorAction,
    ) {}

    /**
     * @param  array<string, mixed>  $rowPayload
     */
    public function execute(
        ImportBatch $batch,
        Member $member,
        array $rowPayload,
        int $rowNumber,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberWorkImportItem {
        $workData = $rowPayload + [
            'agreement_accepted' => (bool) $batch->agreement_accepted,
            'date_of_consent' => $batch->date_of_consent?->toDateString(),
        ];

        $work = $this->createWorkAction->execute(
            $member,
            $workData,
            $actor,
            $ipAddress,
            $userAgent
        );

        $contributorName = trim((string) ($member->user?->name ?? ''));
        if ($contributorName === '') {
            $contributorName = (string) ($member->user?->email ?? 'Member');
        }

        $this->addWorkContributorAction->execute(
            $work,
            [
                'member_id' => $member->id,
                'contributor_name' => $contributorName,
                'contributor_role' => 'Author',
                'right_type' => 'exclusive',
                'ownership_percentage' => 100,
                'territory_scope' => 'Nigeria',
            ],
            $actor,
            $ipAddress,
            $userAgent
        );

        return MemberWorkImportItem::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'work_id' => $work->id,
            'status' => 'draft',
            'row_payload_json' => $rowPayload,
        ]);
    }
}
