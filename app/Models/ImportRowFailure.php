<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRowFailure extends Model
{
    use HasFactory;

    protected $fillable = ['import_batch_id', 'row_number', 'row_payload_json', 'errors_json'];

    protected $casts = [
        'row_payload_json' => 'array',
        'errors_json' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
