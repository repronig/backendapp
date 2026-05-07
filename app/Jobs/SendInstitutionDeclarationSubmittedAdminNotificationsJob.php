<?php

namespace App\Jobs;

use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use App\Notifications\System\InstitutionDeclarationSubmittedAdminSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInstitutionDeclarationSubmittedAdminNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Resolved declaration id (new dispatches set this; {@see __wakeup} may fill it from legacy payloads).
     */
    public int $institutionAnnualDeclarationId = 0;

    /**
     * @deprecated Only present so older queued job serialization (property name `declaration`) hydrates.
     *            Do not read in handle(); use {@see $institutionAnnualDeclarationId} after {@see __wakeup}.
     *
     * Intentionally untyped so PHP never raises "accessed before initialization" when this key is absent.
     */
    public $declaration = null;

    public function __construct(int $institutionAnnualDeclarationId)
    {
        $this->institutionAnnualDeclarationId = $institutionAnnualDeclarationId;
        $this->declaration = null;
    }

    /**
     * Normalize id when the queue still has jobs serialized before the int-id refactor.
     */
    public function __wakeup(): void
    {
        if ($this->institutionAnnualDeclarationId >= 1) {
            $this->declaration = null;

            return;
        }

        $decl = $this->declaration;
        if ($decl instanceof InstitutionAnnualDeclaration) {
            $this->institutionAnnualDeclarationId = (int) $decl->getKey();
        } elseif ($decl instanceof ModelIdentifier) {
            $class = $decl->class ?? null;
            $rawId = $decl->id ?? null;
            $id = null;
            if (is_numeric($rawId)) {
                $id = (int) $rawId;
            } elseif (is_array($rawId) && isset($rawId[0]) && is_numeric($rawId[0])) {
                $id = (int) $rawId[0];
            }
            if ($class === InstitutionAnnualDeclaration::class && $id !== null) {
                $this->institutionAnnualDeclarationId = $id;
            }
        }

        $this->declaration = null;
    }

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->institutionAnnualDeclarationId < 1) {
            return;
        }

        $declaration = InstitutionAnnualDeclaration::query()
            ->with(['institution'])
            ->find($this->institutionAnnualDeclarationId);

        if (! $declaration || ! $declaration->institution) {
            return;
        }

        $institutionName = (string) ($declaration->institution->name ?? '');
        $licensingYear = (string) ($declaration->licensing_year ?? '');
        $admins = User::adminAlertRecipients();

        foreach ($admins as $admin) {
            if ($admin->email) {
                $mailService->sendInstitutionDeclarationSubmittedToAdmin($admin, $declaration);
            }

            $systemNotifications->send(
                $admin,
                new InstitutionDeclarationSubmittedAdminSystemNotification(
                    (int) $declaration->id,
                    $institutionName !== '' ? $institutionName : 'An institution',
                    $licensingYear !== '' ? $licensingYear : '—',
                ),
                'institution_declaration_submitted',
                'Institution declaration submitted'
            );
        }
    }
}
