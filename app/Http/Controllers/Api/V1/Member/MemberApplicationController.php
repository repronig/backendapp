<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\MemberOnboarding\CreateMemberApplicationAction;
use App\Actions\MemberOnboarding\SubmitMemberApplicationAction;
use App\Actions\MemberOnboarding\UpdateMemberApplicationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreMemberApplicationRequest;
use App\Http\Requests\Api\V1\UpdateMemberApplicationRequest;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\MemberApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberApplicationController extends BaseApiController
{
    public function store(
        StoreMemberApplicationRequest $request,
        CreateMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('create', MemberApplication::class);
        $application = $action->execute(
            $request->user(),
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Member application created successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function myApplication(Request $request): JsonResponse
    {
        $application = $request->user()->memberApplication?->load([
            'user',
            'association',
            'documents',
        ]);

        return $this->success(
            'Member application retrieved successfully.',
            $application ? new MemberApplicationResource($application) : null
        );
    }

    public function show(MemberApplication $memberApplication): JsonResponse
    {
        $this->authorize('view', $memberApplication);

        return $this->success(
            'Member application retrieved successfully.',
            new MemberApplicationResource(
                $memberApplication->load(['user', 'association', 'documents'])
            )
        );
    }

    public function update(
        UpdateMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        UpdateMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('update', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application updated successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function submit(
        Request $request,
        MemberApplication $memberApplication,
        SubmitMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('submit', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application submitted successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function downloadMandate(Request $request, MemberApplication $memberApplication): StreamedResponse
    {
        $this->authorize('view', $memberApplication);

        if (! $memberApplication->isApproved()) {
            abort(422, 'Mandate form can only be downloaded after admin approval.');
        }

        $application = $memberApplication->load(['user', 'association']);
        $filename = sprintf('member_application_mandate_%s.txt', $application->application_reference ?? $application->id);
        $content = implode(PHP_EOL, [
            'REPRONIG Member Application Mandate',
            'Application Reference: '.($application->application_reference ?? 'N/A'),
            'Applicant: '.($application->user?->name ?? $application->user?->email ?? 'N/A'),
            'Association: '.($application->association?->name ?? 'N/A'),
            'Application Status: '.($application->application_status ?? 'N/A'),
            'Affiliation Status: '.($application->affiliation_status ?? 'N/A'),
            'Consent Accepted: '.($application->consent_accepted ? 'Yes' : 'No'),
            'Consent Date: '.($application->consent_date?->toDateString() ?? 'N/A'),
            'Submitted At: '.($application->submitted_at?->toIso8601String() ?? 'N/A'),
            'Admin Reviewed At: '.($application->reviewed_at?->toIso8601String() ?? 'N/A'),
        ]);
        $path = sprintf(
            'member-applications/mandates/%s/%s',
            $application->id,
            Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'.txt'
        );
        Storage::disk('public')->put($path, $content);

        return Storage::disk('public')->download($path, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}