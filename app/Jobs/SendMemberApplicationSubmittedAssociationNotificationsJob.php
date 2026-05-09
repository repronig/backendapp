<?php

namespace App\Jobs;

use App\Mail\Associations\MemberApplicationSubmittedAssociationMailable;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\System\MemberApplicationSubmittedAssociationSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched to the default queue (e.g. Redis) like other notification jobs.
 * Email is sent with {@see MailService::sendMailable(..., queueMailable: false)} so the
 * message is not double-queued via a {@see \Illuminate\Contracts\Queue\ShouldQueue} mailable.
 */
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
        $applicantName = trim((string) ($application->user?->name ?? '')) !== ''
            ? (string) $application->user->name
            : (string) ($application->user?->email ?? 'Applicant');
        $applicationRef = (string) ($application->application_reference ?: $application->external_id ?: '');

        $officers = User::query()
            ->where('status', 'active')
            ->whereHas('associations', function ($query) use ($application): void {
                $query->where('associations.id', $application->association_id)
                    ->where('association_user.is_active', true)
                    ->where('associations.is_enabled', true)
                    ->where('associations.status', 'active');
            })
            ->where(function ($query): void {
                $query->where('account_type', 'association_officer')
                    ->orWhereHas('roles', function ($roles): void {
                        $roles->where('name', 'association_officer');
                    });
            })
            ->get()
            ->unique('id')
            ->values();

        if ($officers->isEmpty()) {
            Log::warning('member_application_submitted_no_association_officers', [
                'member_application_id' => $application->id,
                'association_id' => $application->association_id,
            ]);

            $contact = trim((string) ($association?->contact_email ?? ''));
            if ($contact !== '') {
                $fallbackMailable = new MemberApplicationSubmittedAssociationMailable($application);
                $mailService->sendMailable(
                    null,
                    $contact,
                    'member_application_submitted_association',
                    $fallbackMailable->envelope()->subject,
                    $fallbackMailable,
                    ['entity_type' => 'member_application', 'entity_id' => $application->id],
                    queueMailable: false
                );
            }

            return;
        }

        foreach ($officers as $officer) {
            if ($officer->email) {
                $mailService->sendMemberApplicationSubmittedToAssociationOfficer(
                    $officer,
                    $application,
                    new MemberApplicationSubmittedAssociationMailable($application)
                );
            }

            $systemTitle = sprintf('Member Affiliation Request for %s', $applicantName);

            $systemNotifications->send(
                $officer,
                new MemberApplicationSubmittedAssociationSystemNotification(
                    $applicantName,
                    (string) ($association?->name ?? 'Association'),
                    $applicationRef,
                    (int) $application->id
                ),
                'member_application_submitted_association',
                $systemTitle,
                [],
                dispatchSynchronously: true
            );
        }
    }
}
