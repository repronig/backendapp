<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedReportingPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['created_by_user_id', 'name', 'date_from', 'date_to', 'filters_json'];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'filters_json' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
