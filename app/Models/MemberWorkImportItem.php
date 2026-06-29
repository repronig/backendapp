<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberWorkImportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'work_id',
        'status',
        'row_payload_json',
        'readiness_errors_json',
        'file_results_json',
        'submit_errors_json',
    ];

    protected $casts = [
        'row_payload_json' => 'array',
        'readiness_errors_json' => 'array',
        'file_results_json' => 'array',
        'submit_errors_json' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
