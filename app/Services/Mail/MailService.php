<?php

namespace App\Services\Mail;

use App\Mail\Admin\InstitutionDeclarationSubmittedAdminMailable;
use App\Mail\Admin\OfflineInvoicePaymentSubmittedAdminMailable;
use App\Mail\Associations\AssociationDisabledMailable;
use App\Mail\Associations\AssociationEnabledMailable;
use App\Mail\Associations\MemberApplicationSubmittedAssociationMailable;
use App\Mail\Institutions\DeclarationApprovedMailable;
use App\Mail\Institutions\DeclarationRejectedMailable;
use App\Mail\Institutions\InstitutionApprovedMailable;
use App\Mail\Institutions\InstitutionRejectedMailable;
use App\Mail\Institutions\InstitutionWelcomeMailable;
use App\Mail\Institutions\InvoiceDueReminderMailable;
use App\Mail\Institutions\InvoiceGeneratedMailable;
use App\Mail\Institutions\InvoiceOverdueReminderMailable;
use App\Mail\Institutions\OfflineLicencePaymentRejectedMailable;
use App\Mail\Institutions\PaymentInitiatedMailable;
use App\Mail\Members\AdminMemberApprovedMailable;
use App\Mail\Members\MemberWelcomeMailable;
use App\Mail\Payments\PaymentReceivedAdminMailable;
use App\Mail\Payments\PaymentReceivedMailable;
use App\Mail\Works\WorkReviewDecisionMemberMailable;
use App\Mail\Works\WorkSubmittedAdminMailable;
use App\Mail\Works\WorkUpdateRequestedMailable;
use App\Mail\Works\WorkUpdateRequestReviewedMailable;
use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Invoice;
use App\Models\LicencePayment;
use App\Models\MemberApplication;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\Work;
use App\Notifications\Auth\CustomResetPasswordNotification;
use App\Notifications\Auth\CustomVerifyEmailNotification;
use App\Notifications\Members\MemberApplicationApprovedNotification;
use App\Notifications\Members\MemberApplicationChangesRequestedNotification;
use App\Notifications\Members\MemberApplicationRejectedNotification;
use App\Support\Notifications\NotificationChannels;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailService
{
    public function __construct(
        protected NotificationPreferenceResolver $preferenceResolver
    ) {}

    public function sendMemberApplicationApproved(User $user, ?string $memberCode = null): void
    {
        $this->safeNotify($user, new MemberApplicationApprovedNotification($memberCode), 'member_application_approved_email_failed');
    }

    public function sendInstitutionWelcome(Institution $institution): void
    {
        $recipient = $institution->email;

        if (! $recipient) {
            return;
        }

        $this->sendMailable(
            null,
            $recipient,
            'institution_welcome',
            'Welcome to REPRONIG',
            new InstitutionWelcomeMailable($institution, null),
            ['entity_type' => 'institution', 'entity_id' => $institution->id]
        );
    }

    public function sendInstitutionUserWelcome(User $user, Institution $institution): void
    {
        $recipient = (string) $user->email;
        if ($recipient === '') {
            return;
        }

        $this->sendMailable(
            $user->id,
            $recipient,
            'institution_welcome',
            'Welcome to REPRONIG',
            new InstitutionWelcomeMailable($institution, $user),
            ['entity_type' => 'institution', 'entity_id' => $institution->id]
        );
    }

    public function sendMemberWelcome(User $user): void
    {
        $recipient = (string) $user->email;
        if ($recipient === '') {
            return;
        }

        $this->sendMailable(
            $user->id,
            $recipient,
            'member_welcome',
            'Welcome to REPRONIG',
            new MemberWelcomeMailable($user),
            ['entity_type' => 'user', 'entity_id' => $user->id]
        );
    }

    public function sendMemberApplicationRejected(User $user, string $reason): void
    {
        $this->safeNotify($user, new MemberApplicationRejectedNotification($reason), 'member_application_rejected_email_failed');
    }

    public function sendMemberApplicationChangesRequested(User $user, string $comment): void
    {
        $this->safeNotify($user, new MemberApplicationChangesRequestedNotification($comment), 'member_application_changes_requested_email_failed');
    }

    public function sendAdminMemberApprovedNotification(string $recipient, $memberApplication, User $reviewer): void
    {
        $this->sendMailable(
            null,
            $recipient,
            'admin_member_approved',
            'New Member Approved by Association',
            new AdminMemberApprovedMailable($memberApplication, $reviewer),
            ['entity_type' => 'member_application', 'entity_id' => $memberApplication->id ?? null]
        );
    }

    public function sendPaymentReceived(string $recipient, LicencePayment $payment): void
    {
        $this->sendMailable(
            optional($payment->institutionUser)->user_id ?? null,
            $recipient,
            'payment_received',
            'Payment Received',
            new PaymentReceivedMailable($payment),
            ['entity_type' => 'payment', 'entity_id' => $payment->id]
        );
    }

    public function sendPaymentReceivedAdminNotice(User $admin, LicencePayment $payment): void
    {
        $email = (string) $admin->email;
        if ($email === '') {
            return;
        }

        $this->sendMailable(
            $admin->id,
            $email,
            'payment_received_admin',
            'Institution licence payment received',
            new PaymentReceivedAdminMailable($payment),
            ['entity_type' => 'payment', 'entity_id' => $payment->id]
        );
    }

    public function sendAssociationDisabled(string $recipient, Association $association): void
    {
        $this->sendMailable(
            null,
            $recipient,
            'association_disabled',
            'Association Disabled',
            new AssociationDisabledMailable($association),
            ['entity_type' => 'association', 'entity_id' => $association->id]
        );
    }

    public function sendAssociationEnabled(Association $association): void
    {
        if (! $association->contact_email) {
            return;
        }

        $this->sendMailable(
            null,
            $association->contact_email,
            'association_enabled',
            'Association Access Restored',
            new AssociationEnabledMailable($association),
            ['entity_type' => 'association', 'entity_id' => $association->id]
        );
    }

    public function sendMemberApplicationSubmittedToAssociationOfficer(
        User $officer,
        MemberApplication $memberApplication,
        MemberApplicationSubmittedAssociationMailable $mailable
    ): void {
        $email = (string) $officer->email;
        if ($email === '') {
            return;
        }

        $subject = $mailable->envelope()->subject;

        $this->sendMailable(
            $officer->id,
            $email,
            'member_application_submitted_association',
            $subject,
            $mailable,
            ['entity_type' => 'member_application', 'entity_id' => $memberApplication->id]
        );
    }

    public function sendInstitutionApproved(Institution $institution): void
    {
        if (! $institution->email) {
            return;
        }

        $this->sendMailable(
            null,
            $institution->email,
            'institution_approved',
            'Institution Approval Confirmation',
            new InstitutionApprovedMailable($institution),
            ['entity_type' => 'institution', 'entity_id' => $institution->id]
        );
    }

    public function sendInstitutionRejected(Institution $institution, ?string $reason = null): void
    {
        if (! $institution->email) {
            return;
        }

        $this->sendMailable(
            null,
            $institution->email,
            'institution_rejected',
            'Institution Registration Decision',
            new InstitutionRejectedMailable($institution, $reason),
            ['entity_type' => 'institution', 'entity_id' => $institution->id, 'reason' => $reason]
        );
    }

    public function sendDeclarationApproved(InstitutionAnnualDeclaration $declaration): void
    {
        if (! $declaration->institution?->email) {
            return;
        }

        $this->sendMailable(
            null,
            $declaration->institution->email,
            'declaration_approved',
            'Annual Declaration Approved',
            new DeclarationApprovedMailable($declaration),
            ['entity_type' => 'declaration', 'entity_id' => $declaration->id]
        );
    }

    public function sendDeclarationRejected(InstitutionAnnualDeclaration $declaration, ?string $reason = null): void
    {
        if (! $declaration->institution?->email) {
            return;
        }

        $this->sendMailable(
            null,
            $declaration->institution->email,
            'declaration_rejected',
            'Annual Declaration Rejected',
            new DeclarationRejectedMailable($declaration, $reason),
            ['entity_type' => 'declaration', 'entity_id' => $declaration->id, 'reason' => $reason]
        );
    }

    public function sendInstitutionDeclarationSubmittedToAdmin(User $admin, InstitutionAnnualDeclaration $declaration): void
    {
        $email = (string) $admin->email;
        if ($email === '') {
            return;
        }

        $this->sendMailable(
            $admin->id,
            $email,
            'institution_declaration_submitted',
            'Institution annual declaration submitted',
            new InstitutionDeclarationSubmittedAdminMailable($declaration),
            ['entity_type' => 'declaration', 'entity_id' => $declaration->id]
        );
    }

    public function sendOfflineInvoicePaymentSubmittedToAdmin(User $admin, LicencePayment $payment): void
    {
        $email = (string) $admin->email;
        if ($email === '') {
            return;
        }

        $this->sendMailable(
            $admin->id,
            $email,
            'offline_invoice_payment_submitted_admin',
            'Offline invoice payment submitted for review',
            new OfflineInvoicePaymentSubmittedAdminMailable($payment),
            ['entity_type' => 'payment', 'entity_id' => $payment->id]
        );
    }

    public function sendInvoiceGenerated(Invoice $invoice): void
    {
        if (! $invoice->institution?->email) {
            return;
        }

        $this->sendMailable(
            null,
            $invoice->institution->email,
            'invoice_generated',
            'New Invoice Generated',
            new InvoiceGeneratedMailable($invoice),
            ['entity_type' => 'invoice', 'entity_id' => $invoice->id]
        );
    }

    public function sendInvoiceDueReminder(Invoice $invoice): void
    {
        if (! $invoice->institution?->email) {
            return;
        }

        $this->sendMailable(
            null,
            $invoice->institution->email,
            'invoice_due_reminder',
            'Invoice Due Reminder',
            new InvoiceDueReminderMailable($invoice),
            ['entity_type' => 'invoice', 'entity_id' => $invoice->id]
        );
    }

    public function sendInvoiceOverdueReminder(Invoice $invoice): void
    {
        if (! $invoice->institution?->email) {
            return;
        }

        $this->sendMailable(
            null,
            $invoice->institution->email,
            'invoice_overdue_reminder',
            'Overdue Invoice Notice',
            new InvoiceOverdueReminderMailable($invoice),
            ['entity_type' => 'invoice', 'entity_id' => $invoice->id]
        );
    }

    public function sendPaymentInitiated(Institution $institution, LicencePayment $payment): void
    {
        if (! $institution->email) {
            return;
        }

        $this->sendMailable(
            null,
            $institution->email,
            'payment_initiated',
            'Payment initiated',
            new PaymentInitiatedMailable($payment),
            ['entity_type' => 'payment', 'entity_id' => $payment->id]
        );
    }

    public function sendOfflineLicencePaymentRejected(LicencePayment $payment, ?string $reason = null): void
    {
        if (! $payment->institution?->email) {
            return;
        }

        $this->sendOfflineLicencePaymentRejectedTo(
            null,
            $payment->institution->email,
            $payment,
            $reason
        );
    }

    public function sendOfflineLicencePaymentRejectedTo(?int $userId, string $recipient, LicencePayment $payment, ?string $reason = null): void
    {
        if ($recipient === '') {
            return;
        }

        $this->sendMailable(
            $userId,
            $recipient,
            'offline_licence_payment_rejected',
            'Offline Licence Payment Rejected',
            new OfflineLicencePaymentRejectedMailable($payment, $reason),
            ['entity_type' => 'payment', 'entity_id' => $payment->id, 'reason' => $reason]
        );
    }

    public function sendWorkUpdateRequested(string $recipient, int|string|null $workId, WorkUpdateRequestedMailable $mailable): void
    {
        $this->sendMailable(
            null,
            $recipient,
            'work_update_requested',
            'Work update request submitted',
            $mailable,
            ['entity_type' => 'work', 'entity_id' => $workId]
        );
    }

    public function sendWorkUpdateReviewed(string $recipient, int|string|null $workId, string $decision, WorkUpdateRequestReviewedMailable $mailable): void
    {
        $this->sendMailable(
            null,
            $recipient,
            'work_update_request_reviewed',
            $decision === 'approved' ? 'Work update request approved' : 'Work update request rejected',
            $mailable,
            ['entity_type' => 'work', 'entity_id' => $workId, 'decision' => $decision]
        );
    }

    public function sendWorkSubmittedToAdmin(User $admin, Work $work, WorkSubmittedAdminMailable $mailable): void
    {
        $email = (string) $admin->email;
        if ($email === '') {
            return;
        }

        $this->sendMailable(
            $admin->id,
            $email,
            'work_submitted_admin',
            'New work submitted for review',
            $mailable,
            ['entity_type' => 'work', 'entity_id' => $work->id]
        );
    }

    public function sendWorkReviewDecisionToMember(User $memberUser, Work $work, string $decision, WorkReviewDecisionMemberMailable $mailable): void
    {
        $email = (string) $memberUser->email;
        if ($email === '') {
            return;
        }

        $subject = match ($decision) {
            'verified' => 'Your work has been verified',
            'approved' => 'Your work has been approved',
            'rejected' => 'Your work was rejected',
            'changes_requested' => 'Changes requested for your work',
            default => 'Work review update',
        };

        $this->sendMailable(
            $memberUser->id,
            $email,
            'work_reviewed_member',
            $subject,
            $mailable,
            ['entity_type' => 'work', 'entity_id' => $work->id, 'decision' => $decision]
        );
    }

    public function sendPasswordReset(User $user, string $token): void
    {
        $this->safeNotify($user, new CustomResetPasswordNotification($token), 'password_reset_email_failed');
    }

    public function sendEmailVerification(User $user): void
    {
        $this->safeNotify($user, new CustomVerifyEmailNotification, 'email_verification_email_failed');
    }

    protected function safeNotify(User $user, object $notification, string $logMessage): void
    {
        $notificationKey = method_exists($notification, 'preferenceKey')
            ? $notification->preferenceKey()
            : strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', class_basename($notification)));
        $payload = method_exists($notification, 'toMail')
            ? ['notification' => get_class($notification)]
            : [];
        $idempotencyKey = $this->buildIdempotencyKey(
            (int) $user->id,
            (string) $user->email,
            $notificationKey,
            class_basename($notification),
            $payload,
            ['notification' => get_class($notification)]
        );

        if ($this->deliveryAlreadyLogged($idempotencyKey)) {
            return;
        }

        if (! $this->preferenceResolver->shouldSend($user, $notificationKey, NotificationChannels::EMAIL)) {
            $this->queueNotificationLog(
                $user->id,
                $notificationKey,
                NotificationChannels::EMAIL,
                class_basename($notification),
                $idempotencyKey,
                $payload
            )
                ->update([
                    'status' => 'skipped',
                    'failure_reason' => 'Notification preference disabled for email channel.',
                ]);

            return;
        }

        $log = $this->queueNotificationLog(
            $user->id,
            $notificationKey,
            NotificationChannels::EMAIL,
            class_basename($notification),
            $idempotencyKey,
            $payload
        );

        try {
            $user->notify($notification);
            $this->markNotificationSent($log);
        } catch (Throwable $e) {
            $this->markNotificationFailed($log, $e->getMessage());
            Log::error($logMessage, [
                'user_id' => $user->id,
                'email' => $user->email,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendMailable(
        ?int $userId,
        string $recipient,
        string $notificationKey,
        string $subject,
        Mailable $mailable,
        array $context = []
    ): void {
        if ($recipient === '') {
            return;
        }

        $payload = ['mailable' => get_class($mailable)];
        $idempotencyKey = $this->buildIdempotencyKey($userId, $recipient, $notificationKey, $subject, $payload, $context);

        if ($this->deliveryAlreadyLogged($idempotencyKey)) {
            return;
        }

        $log = $this->queueNotificationLog($userId, $notificationKey, NotificationChannels::EMAIL, $subject, $idempotencyKey, $payload);

        try {
            Mail::to($recipient)->queue($mailable);
            $this->markNotificationSent($log);
        } catch (Throwable $e) {
            $this->markNotificationFailed($log, $e->getMessage());
            Log::error('email_notification_failed', [
                'recipient' => $recipient,
                'notification_key' => $notificationKey,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function queueNotificationLog(
        ?int $userId,
        string $key,
        string $channel,
        ?string $subject = null,
        ?string $idempotencyKey = null,
        ?array $payload = null
    ): NotificationLog {
        if ($idempotencyKey) {
            return NotificationLog::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'user_id' => $userId,
                    'notification_key' => $key,
                    'channel' => $channel,
                    'status' => 'queued',
                    'subject' => $subject,
                    'payload_json' => $payload,
                ]
            );
        }

        return NotificationLog::create([
            'user_id' => $userId,
            'notification_key' => $key,
            'channel' => $channel,
            'idempotency_key' => $idempotencyKey,
            'status' => 'queued',
            'subject' => $subject,
            'payload_json' => $payload,
        ]);
    }

    protected function markNotificationSent(NotificationLog $log): void
    {
        $log->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    protected function markNotificationFailed(NotificationLog $log, string $reason): void
    {
        $log->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    protected function deliveryAlreadyLogged(string $idempotencyKey): bool
    {
        return NotificationLog::query()
            ->where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['queued', 'sent', 'skipped'])
            ->exists();
    }

    protected function buildIdempotencyKey(
        ?int $userId,
        string $recipient,
        string $notificationKey,
        string $subject,
        array $payload = [],
        array $context = []
    ): string {
        return hash('sha256', json_encode([
            'channel' => NotificationChannels::EMAIL,
            'user_id' => $userId,
            'recipient' => strtolower($recipient),
            'notification_key' => $notificationKey,
            'subject' => $subject,
            'payload' => Arr::sortRecursive($payload),
            'context' => Arr::sortRecursive($context),
        ]));
    }
}
