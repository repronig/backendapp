<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'created_by_user_id', 'adjustment_type', 'amount', 'reason_code', 'reason', 'metadata_json', 'applied_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata_json' => 'array',
        'applied_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
