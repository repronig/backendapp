<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Access\LoginUserAction;
use App\Actions\Access\RegisterInstitutionAction;
use App\Actions\Access\RegisterMemberAction;
use App\Actions\Access\ResendInstitutionRegistrationOtpAction;
use App\Actions\Access\ResendMemberRegistrationOtpAction;
use App\Actions\Access\VerifyInstitutionRegistrationOtpAction;
use App\Actions\Access\VerifyMemberRegistrationOtpAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Auth\ResendInstitutionRegistrationOtpRequest;
use App\Http\Requests\Api\V1\Auth\ResendMemberRegistrationOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyInstitutionRegistrationOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyMemberRegistrationOtpRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterInstitutionRequest;
use App\Http\Requests\Api\V1\RegisterMemberRequest;
use App\Http\Resources\Api\V1\AuthSessionResource;
use App\Http\Resources\Api\V1\RegisteredInstitutionResource;
use App\Http\Resources\Api\V1\RegisteredMemberResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class AuthController extends BaseApiController
{
    public function registerMember(
        RegisterMemberRequest $request,
        RegisterMemberAction $action
    ): JsonResponse {
        $result = $action->execute(
            Arr::except($request->validated(), ['recaptcha_token']),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Member account created successfully. Please verify the OTP sent to your email.',
            new RegisteredMemberResource($result)
        );
    }

    public function verifyMemberOtp(
        VerifyMemberRegistrationOtpRequest $request,
        VerifyMemberRegistrationOtpAction $action
    ): JsonResponse {
        $result = $action->execute(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Email verified successfully.',
            new AuthSessionResource($result)
        );
    }

    public function resendMemberOtp(
        ResendMemberRegistrationOtpRequest $request,
        ResendMemberRegistrationOtpAction $action
    ): JsonResponse {
        $result = $action->execute($request->validated());

        return $this->success('A new OTP has been sent to your email and SMS.', [
            'expires_at' => $result['expires_at'] ?? null,
            'otp_delivery' => $result['delivery'] ?? null,
        ]);
    }

    public function verifyInstitutionOtp(
        VerifyInstitutionRegistrationOtpRequest $request,
        VerifyInstitutionRegistrationOtpAction $action
    ): JsonResponse {
        $result = $action->execute(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Email verified successfully.',
            new AuthSessionResource($result)
        );
    }

    public function resendInstitutionOtp(
        ResendInstitutionRegistrationOtpRequest $request,
        ResendInstitutionRegistrationOtpAction $action
    ): JsonResponse {
        $result = $action->execute($request->validated());

        return $this->success('A new OTP has been sent to your email and SMS.', [
            'expires_at' => $result['expires_at'] ?? null,
            'otp_delivery' => $result['delivery'] ?? null,
        ]);
    }

    public function registerInstitution(
        RegisterInstitutionRequest $request,
        RegisterInstitutionAction $action
    ): JsonResponse {
        $result = $action->execute(
            Arr::except($request->validated(), ['recaptcha_token']),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Institution account created successfully. Please verify the OTP sent to your email.',
            new RegisteredInstitutionResource($result)
        );
    }

    public function login(
        LoginRequest $request,
        LoginUserAction $action
    ): JsonResponse {
        try {
            $result = $action->execute(
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return $this->success(
                'Login successful.',
                new AuthSessionResource($result)
            );
        } catch (AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function logout(): JsonResponse
    {
        auth()->user()?->currentAccessToken()?->delete();

        return $this->success('Logout successful.');
    }
}
