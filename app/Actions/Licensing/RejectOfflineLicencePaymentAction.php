<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\LicencePaymentStatus;
use App\Models\LicencePayment;
use App\Models\User;
use App\Notifications\System\OfflineLicencePaymentRejectedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RejectOfflineLicencePaymentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {}

    public function execute(LicencePayment $payment, User $admin, string $reason, ?string $ipAddress = null, ?string $userAgent = null): LicencePayment
    {
        if ($payment->gateway_name !== 'offline') {
            throw ValidationException::withMessages(['payment' => ['Only offline payments can be rejected from this action.']]);
        }

        if ($payment->payment_status !== LicencePaymentStatus::PendingOffline->value) {
            throw ValidationException::withMessages(['payment' => ['This offline payment is not awaiting confirmation.']]);
        }

        $fresh = DB::transaction(function () use ($payment, $admin, $reason, $ipAddress, $userAgent): LicencePayment {
            $locked = LicencePayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($locked->payment_status !== LicencePaymentStatus::PendingOffline->value) {
                throw ValidationException::withMessages(['payment' => ['This offline payment is no longer awaiting confirmation.']]);
            }

            $before = $locked->toArray();
            $raw = (array) ($locked->raw_response_json ?? []);
            $offline = (array) ($raw['offline'] ?? []);
            $disk = (string) ($offline['proof_disk'] ?? 'local');
            $path = (string) ($offline['proof_disk_path'] ?? '');
            if ($path !== '') {
                Storage::disk($disk)->delete($path);
            }
            $offline['rejection_reason'] = $reason;
            $offline['rejected_at'] = now()->toIso8601String();
            $offline['rejected_by_user_id'] = $admin->id;
            unset($offline['proof_disk_path'], $offline['proof_disk']);
            $raw['offline'] = $offline;

            $locked->update([
                'payment_status' => LicencePaymentStatus::Cancelled->value,
                'raw_response_json' => $raw,
            ]);

            $fresh = $locked->fresh(['invoice', 'licence', 'declaration', 'institution']);

            $this->logAuditAction->execute(
                $admin,
                'offline_licence_payment_rejected',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });

        $fresh->loadMissing(['institution.institutionUsers.user', 'invoice', 'licence', 'declaration', 'institution']);

        $emails = collect([
            (string) ($fresh->institution?->email ?? ''),
            ...collect($fresh->institution?->institutionUsers ?? [])
                ->filter(fn ($iu) => $iu && $iu->is_active && $iu->user && (string) ($iu->user->email ?? '') !== '')
                ->map(fn ($iu) => (string) $iu->user->email)
                ->all(),
        ])
            ->filter(fn (string $email) => $email !== '')
            ->unique()
            ->values();

        foreach ($emails as $email) {
            $userId = null;
            foreach ($fresh->institution?->institutionUsers ?? [] as $institutionUser) {
                if ($institutionUser->is_active && $institutionUser->user && (string) $institutionUser->user->email === $email) {
                    $userId = (int) $institutionUser->user->id;
                    break;
                }
            }

            $this->mailService->sendOfflineLicencePaymentRejectedTo($userId, $email, $fresh, $reason);
        }

        $currencyPrefix = strtoupper((string) $fresh->currency) === 'NGN' ? '₦' : $fresh->currency.' ';
        $amountFormatted = $currencyPrefix.number_format((float) ($fresh->amount_allocated ?: $fresh->amount), 2);

        foreach ($fresh->institution?->institutionUsers ?? [] as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $this->systemNotifications->send(
                $institutionUser->user,
                new OfflineLicencePaymentRejectedSystemNotification(
                    (string) $fresh->payment_reference,
                    $amountFormatted,
                    $fresh->licence?->licence_number ?? $fresh->licence?->external_id ?? null,
                    $reason,
                    $fresh->id
                ),
                'offline_licence_payment_rejected',
                'Offline payment rejected'
            );
        }

        return $fresh;
    }
}
