<?php

namespace App\Actions\Imports;

use App\Models\ImportBatch;
use App\Models\User;

class CreateImportBatchAction
{
    public function execute(User $actor, string $type, string $sourceFilename, int $totalRows = 0): ImportBatch
    {
        return ImportBatch::create([
            'created_by_user_id' => $actor->id,
            'import_type' => $type,
            'status' => 'pending',
            'source_filename' => $sourceFilename,
            'total_rows' => $totalRows,
        ]);
    }
}
