<?php

namespace App\Policies;

use App\Enums\WorkStatus;
use App\Models\User;
use App\Models\Work;
use App\Policies\Concerns\HandlesAdminOverride;

class WorkPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, Work $work): bool
    {
        return $user->hasRole('member')
            && (int) optional($user->member)->id === (int) $work->member_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('member') && $user->member !== null;
    }

    public function update(User $user, Work $work): bool
    {
        return $user->hasRole('member')
            && (int) optional($user->member)->id === (int) $work->member_id
            && (
                $work->work_status === WorkStatus::Draft
                || $work->work_status === WorkStatus::ChangesRequested
                || ($work->work_status === WorkStatus::Approved && $work->update_request_status === 'approved')
            )
            && ! $work->is_restricted;
    }

    public function submit(User $user, Work $work): bool
    {
        return $user->hasRole('member')
            && (int) optional($user->member)->id === (int) $work->member_id
            && in_array($work->work_status, [WorkStatus::Draft, WorkStatus::ChangesRequested], true)
            && ! $work->is_restricted;
    }

    public function review(User $user, Work $work): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function requestUpdate(User $user, Work $work): bool
    {
        return $user->hasRole('member')
            && (int) optional($user->member)->id === (int) $work->member_id
            && $work->work_status === WorkStatus::Approved
            && ! $work->is_restricted
            && $work->update_request_status !== 'pending';
    }

    public function delete(User $user, Work $work): bool
    {
        return $user->hasRole('member')
            && (int) optional($user->member)->id === (int) $work->member_id
            && $work->work_status === WorkStatus::Draft;
    }
}
