<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'date_of_birth',
        'occupation',
        'residential_address_line_1',
        'residential_address_line_2',
        'city',
        'state',
        'city_id',
        'state_id',
        'country',
        'postal_code',
        'publisher_name',
        'corporate_name',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
