<?php

namespace App\Actions\Imports;

use App\Models\ImportBatch;
use App\Models\Member;
use App\Models\User;

class CreateImportBatchAction
{
    public function execute(User $actor, string $type, string $sourceFilename, int $totalRows = 0, ?Member $member = null): ImportBatch
    {
        return ImportBatch::create([
            'created_by_user_id' => $actor->id,
            'member_id' => $member?->id,
            'import_type' => $type,
            'status' => 'pending',
            'source_filename' => $sourceFilename,
            'total_rows' => $totalRows,
        ]);
    }
}
