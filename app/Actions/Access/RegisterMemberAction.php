<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Security\StartSecurityChallengeAction;
use App\Enums\MemberApplicationStatus;
use App\Models\MemberApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterMemberAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected StartSecurityChallengeAction $startSecurityChallengeAction
    ) {
    }

    public function execute(
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'password' => Hash::make($data['password']),
                'account_type' => 'member',
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            $user->assignRole('member');

            $application = MemberApplication::create([
                'user_id' => $user->id,
                'association_id' => $data['association_id'],
                'applicant_type' => $data['applicant_type'],
                'application_status' => MemberApplicationStatus::Draft->value,
                'submission_stage' => 'account_created',
            ]);

            $freshUser = $user->fresh()->load('roles');
            $freshApplication = $application->fresh(['association', 'documents', 'user']);

            $this->logAuditAction->execute(
                $freshUser,
                'member_account_registered',
                $freshUser,
                null,
                $freshUser->toArray(),
                $ipAddress,
                $userAgent
            );

            $this->logAuditAction->execute(
                $freshUser,
                'member_application_created_from_registration',
                $freshApplication,
                null,
                $freshApplication->toArray(),
                $ipAddress,
                $userAgent
            );

            $challenge = $this->startSecurityChallengeAction->execute($freshUser, 'member_registration_otp');

            return [
                'user' => $freshUser,
                'member_application' => $freshApplication,
                'otp_expires_at' => $challenge['expires_at'],
                'otp_delivery' => $challenge['delivery'] ?? null,
            ];
        });
    }
}
