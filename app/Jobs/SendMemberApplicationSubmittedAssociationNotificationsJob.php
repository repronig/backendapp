<?php

namespace App\Jobs;

use App\Mail\Associations\MemberApplicationSubmittedAssociationMailable;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\System\MemberApplicationSubmittedAssociationSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberApplicationSubmittedAssociationNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $memberApplicationId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->memberApplicationId < 1) {
            return;
        }

        $application = MemberApplication::query()
            ->with(['user', 'association'])
            ->find($this->memberApplicationId);

        if (! $application || ! $application->association_id) {
            return;
        }

        $association = $application->association;
        $applicantName = (string) ($application->user?->name ?: 'A member');
        $applicationRef = (string) ($application->application_reference ?: $application->external_id ?: '');

        $officers = User::query()
            ->where('status', 'active')
            ->whereHas('associations', function ($query) use ($application): void {
                $query->where('associations.id', $application->association_id)
                    ->where('association_user.is_active', true)
                    ->where('associations.is_enabled', true)
                    ->where('associations.status', 'active');
            })
            ->where(function (Builder $query): void {
                $query->where('account_type', 'association_officer')
                    ->orWhereHas('roles', function ($roles): void {
                        $roles->where('guard_name', (string) config('auth.defaults.guard', 'web'))
                            ->where('name', 'association_officer');
                    });
            })
            ->get()
            ->unique('id')
            ->values();

        foreach ($officers as $officer) {
            if ($officer->email) {
                $mailService->sendMemberApplicationSubmittedToAssociationOfficer(
                    $officer,
                    $application,
                    new MemberApplicationSubmittedAssociationMailable($application)
                );
            }

            $systemNotifications->send(
                $officer,
                new MemberApplicationSubmittedAssociationSystemNotification(
                    $applicantName,
                    (string) ($association?->name ?? 'Association'),
                    $applicationRef,
                    (int) $application->id
                ),
                'member_application_submitted_association',
                'New member application submitted'
            );
        }
    }
}
