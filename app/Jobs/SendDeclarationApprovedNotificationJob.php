<?php

namespace App\Jobs;

use App\Models\InstitutionAnnualDeclaration;
use App\Notifications\System\DeclarationApprovedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDeclarationApprovedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public InstitutionAnnualDeclaration $declaration) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $declaration = $this->declaration->fresh(['institution.institutionUsers.user', 'licence', 'invoice']);

        $mailService->sendDeclarationApproved($declaration);

        foreach ($declaration->institution->institutionUsers as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $systemNotifications->send(
                $institutionUser->user,
                new DeclarationApprovedSystemNotification(
                    $declaration->id,
                    $declaration->licence?->licence_number ?? $declaration->licence?->external_id,
                    (string) $declaration->licensing_year,
                ),
                'declaration_approved',
                'Declaration approved'
            );
        }
    }
}
