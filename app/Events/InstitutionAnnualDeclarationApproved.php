<?php

namespace App\Events;

use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstitutionAnnualDeclarationApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public InstitutionAnnualDeclaration $declaration, public User $approvedBy) {}
}
