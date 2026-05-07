<?php

namespace App\Events;

use App\Models\Association;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssociationDisabled
{
    use Dispatchable, SerializesModels;

    public function __construct(public Association $association, public User $disabledBy) {}
}
