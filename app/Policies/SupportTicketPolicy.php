<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['member', 'association_officer', 'institution_user', 'admin', 'super_admin']);
    }

    public function view(User $user, SupportTicket $supportTicket): bool
    {
        return $supportTicket->user_id === $user->id
            || $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['member', 'association_officer', 'institution_user']);
    }

    public function reply(User $user, SupportTicket $supportTicket): bool
    {
        return $supportTicket->user_id === $user->id
            || $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, SupportTicket $supportTicket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function addInternalNote(User $user, SupportTicket $supportTicket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
