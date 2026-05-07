<?php

namespace App\Actions\MemberOnboarding;

use App\Actions\Audit\LogAuditAction;
use App\Enums\MemberApplicationStatus;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMemberApplicationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        User $user,
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplication {
        return DB::transaction(function () use ($user, $data, $ipAddress, $userAgent): MemberApplication {
            SyncMemberApplicantLegalNames::fromApplicationPayload($user, $data);

            $existing = MemberApplication::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $before = $existing?->toArray();

            if ($existing && $existing->isApproved()) {
                throw ValidationException::withMessages([
                    'member_application' => ['This user already has an approved member application and cannot create another one.'],
                ]);
            }

            if ($existing && ! $existing->isEditableByApplicant()) {
                throw ValidationException::withMessages([
                    'member_application' => ['This user already has a member application and cannot create another one.'],
                ]);
            }

            if ($existing) {
                $existing->fill($data);

                if ($existing->application_status === MemberApplicationStatus::Draft->value) {
                    $existing->submission_stage = 'profile_incomplete';
                }

                $existing->save();

                $fresh = $existing->fresh(['association', 'documents', 'user']);

                $this->logAuditAction->execute(
                    $user,
                    'member_application_upserted',
                    $fresh,
                    $before,
                    $fresh->toArray(),
                    $ipAddress,
                    $userAgent
                );

                return $fresh;
            }

            $application = MemberApplication::create($data + [
                'user_id' => $user->id,
                'application_status' => MemberApplicationStatus::Draft->value,
                'submission_stage' => 'profile_incomplete',
            ]);

            $fresh = $application->fresh(['association', 'documents', 'user']);

            $this->logAuditAction->execute(
                $user,
                'member_application_created',
                $fresh,
                null,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
