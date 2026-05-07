<?php

namespace App\Events;

use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberApplicationApprovedByAssociation
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MemberApplication $memberApplication,
        public Member $member,
        public User $reviewer,
    ) {}
}
