<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'licence_payment_id', 'invoice_id', 'processed_by_user_id', 'status', 'reason_code', 'note', 'before_json', 'after_json', 'processed_at'
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
        'processed_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(LicencePayment::class, 'licence_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
